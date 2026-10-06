<?php

namespace App\Services\Azad;

use App\Enums\ReservationStatus;
use App\Models\AzadProgram;
use App\Models\AzadSelectionItem;
use App\Models\AzadSelectionPlan;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A reservation's Azad university choices: one editable plan per reservation, built from
 * azad_programs by unit code + field code, published and shown to the student like the
 * state plans (FieldSelectionService) but stored apart from them.
 */
class AzadSelectionService
{
    public function __construct(private readonly ActivityLogService $activityLog)
    {
    }

    public function getOrCreatePlan(Reservation $reservation, User $user): AzadSelectionPlan
    {
        $this->assertEligible($reservation);

        return DB::transaction(function () use ($reservation, $user): AzadSelectionPlan {
            Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $plan = AzadSelectionPlan::query()
                ->where('reservation_id', $reservation->id)
                ->where('status', '!=', AzadSelectionPlan::STATUS_ARCHIVED)
                ->latest('version')
                ->first();

            if ($plan) {
                return $plan;
            }

            $plan = AzadSelectionPlan::query()->create([
                'reservation_id' => $reservation->id,
                'student_id' => $reservation->student_id,
                'version' => ((int) AzadSelectionPlan::withTrashed()->where('reservation_id', $reservation->id)->max('version')) + 1,
                'status' => AzadSelectionPlan::STATUS_DRAFT,
                'is_public_visible' => false,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $this->activityLog->log('azad_selection_plan_created', $reservation, $user, null, ['plan_id' => $plan->id, 'version' => $plan->version]);

            return $plan;
        });
    }

    /**
     * Programmes with this unit code and field code (both part-time and full-time, every booklet).
     *
     * @return Collection<int, AzadProgram>
     */
    public function lookup(string $unitCode, string $fieldCode, ?string $booklet = null): Collection
    {
        return AzadProgram::query()
            ->where('is_active', true)
            ->where('unit_code', AzadProgram::asciiDigits(trim($unitCode)))
            ->where('field_code', AzadProgram::asciiDigits(trim($fieldCode)))
            ->when(filled($booklet), fn ($q) => $q->where('booklet', $booklet))
            ->orderByDesc('year')
            ->orderBy('booklet')
            ->orderBy('part_time')
            ->get();
    }

    public function addProgram(AzadSelectionPlan $plan, AzadProgram $program, User $user, ?string $note = null): AzadSelectionItem
    {
        $this->assertEditable($plan);

        if (! $program->is_active) {
            throw ValidationException::withMessages(['azad_program_id' => 'این رشته‌محل غیرفعال است.']);
        }

        return DB::transaction(function () use ($plan, $program, $user, $note): AzadSelectionItem {
            $items = AzadSelectionItem::query()->where('azad_selection_plan_id', $plan->id)->lockForUpdate()->get();

            if ($items->count() >= AzadSelectionPlan::MAX_ITEMS) {
                throw ValidationException::withMessages(['azad_program_id' => 'حداکثر تعداد انتخاب‌ها '.\App\Support\PersianDate::number(AzadSelectionPlan::MAX_ITEMS).' مورد است.']);
            }

            if ($items->contains(fn (AzadSelectionItem $item) => (int) $item->azad_program_id === (int) $program->id)) {
                throw ValidationException::withMessages(['azad_program_id' => 'این رشته‌محل قبلاً در لیست ثبت شده است.']);
            }

            $item = $plan->items()->create([
                ...$program->only(AzadSelectionItem::PROGRAM_FIELDS),
                'azad_program_id' => $program->id,
                'priority_order' => ((int) $items->max('priority_order')) + 1,
                'note' => filled($note) ? trim($note) : null,
            ]);
            $plan->forceFill(['updated_by' => $user->id])->save();
            $this->activityLog->log('azad_selection_item_added', $plan->reservation, $user, null, [
                'plan_id' => $plan->id, 'item_id' => $item->id, 'unit_code' => $item->unit_code, 'field_code' => $item->field_code,
            ]);

            return $item;
        });
    }

    public function updateNote(AzadSelectionItem $item, ?string $note, User $user): AzadSelectionItem
    {
        $item->loadMissing('plan.reservation');
        $this->assertEditable($item->plan);
        $note = filled($note) ? trim($note) : null;

        if ($item->note !== $note) {
            $old = ['note' => $item->note];
            $item->update(['note' => $note]);
            $item->plan->forceFill(['updated_by' => $user->id])->save();
            $this->activityLog->log('azad_selection_item_updated', $item->plan->reservation, $user, $old, ['plan_id' => $item->plan->id, 'item_id' => $item->id, 'note' => $note]);
        }

        return $item;
    }

    public function deleteItem(AzadSelectionItem $item, User $user): void
    {
        $item->loadMissing('plan.reservation');
        $plan = $item->plan;
        $this->assertEditable($plan);

        DB::transaction(function () use ($item, $plan, $user): void {
            $item->delete();
            $this->renumber($plan, $plan->items()->pluck('id')->all());
            $plan->forceFill(['updated_by' => $user->id])->save();
            $this->activityLog->log('azad_selection_item_deleted', $plan->reservation, $user, null, [
                'plan_id' => $plan->id, 'item_id' => $item->id, 'unit_code' => $item->unit_code, 'field_code' => $item->field_code,
            ]);
        });
    }

    /** @param list<int|string> $orderedItemIds */
    public function reorder(AzadSelectionPlan $plan, array $orderedItemIds, User $user): void
    {
        $this->assertEditable($plan);

        DB::transaction(function () use ($plan, $orderedItemIds, $user): void {
            $actual = AzadSelectionItem::query()->where('azad_selection_plan_id', $plan->id)->lockForUpdate()->pluck('id')
                ->map(fn ($id) => (int) $id)->sort()->values()->all();
            $submitted = collect($orderedItemIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($actual !== $submitted) {
                throw ValidationException::withMessages(['ordered_item_ids' => 'ترتیب انتخاب‌ها معتبر نیست.']);
            }

            $this->renumber($plan, array_map('intval', $orderedItemIds));
            $plan->forceFill(['updated_by' => $user->id])->save();
            $this->activityLog->log('azad_selection_reordered', $plan->reservation, $user, null, ['plan_id' => $plan->id]);
        });
    }

    public function publish(AzadSelectionPlan $plan, User $user, bool $visibleToStudent = false): AzadSelectionPlan
    {
        if (! $plan->isDraft()) {
            throw ValidationException::withMessages(['plan' => 'فقط انتخاب رشته پیش‌نویس قابل انتشار است.']);
        }

        if (! $plan->items()->exists()) {
            throw ValidationException::withMessages(['plan' => 'برای انتشار حداقل یک رشته‌محل ثبت کنید.']);
        }

        return DB::transaction(function () use ($plan, $user, $visibleToStudent): AzadSelectionPlan {
            $plan->forceFill([
                'status' => AzadSelectionPlan::STATUS_PUBLISHED,
                'published_at' => now(),
                'updated_by' => $user->id,
            ])->save();
            $this->activityLog->log('azad_selection_published', $plan->reservation, $user, null, ['plan_id' => $plan->id, 'version' => $plan->version]);

            if ($visibleToStudent) {
                $this->setVisibility($plan, true, $user);
            }

            return $plan;
        });
    }

    public function setVisibility(AzadSelectionPlan $plan, bool $visible, User $user): AzadSelectionPlan
    {
        if ($visible && ! $plan->isPublished()) {
            throw ValidationException::withMessages(['plan' => 'فقط نسخه منتشرشده برای دانش‌آموز قابل نمایش است.']);
        }

        $plan->forceFill([
            'is_public_visible' => $visible,
            'student_visible_at' => $visible ? now() : null,
            'student_hidden_at' => $visible ? null : now(),
            'visibility_changed_by' => $user->id,
            'updated_by' => $user->id,
        ])->save();
        $this->activityLog->log($visible ? 'azad_selection_shown_to_student' : 'azad_selection_hidden_from_student', $plan->reservation, $user, null, ['plan_id' => $plan->id]);

        if ($visible) {
            $this->keepPublicLinkAccessible($plan->reservation);
        }

        return $plan;
    }

    public function archive(AzadSelectionPlan $plan, User $user): AzadSelectionPlan
    {
        if ($plan->isArchived()) {
            return $plan;
        }

        $plan->forceFill([
            'status' => AzadSelectionPlan::STATUS_ARCHIVED,
            'is_public_visible' => false,
            'student_hidden_at' => now(),
            'visibility_changed_by' => $user->id,
            'updated_by' => $user->id,
        ])->save();
        $this->activityLog->log('azad_selection_archived', $plan->reservation, $user, null, ['plan_id' => $plan->id]);

        return $plan;
    }

    public function deletePlan(AzadSelectionPlan $plan, User $user): void
    {
        $plan->forceFill(['is_public_visible' => false, 'student_hidden_at' => now(), 'visibility_changed_by' => $user->id, 'updated_by' => $user->id])->save();
        $plan->delete();
        $this->activityLog->log('azad_selection_plan_deleted', $plan->reservation, $user, null, ['plan_id' => $plan->id]);
    }

    /** @return Collection<int, AzadSelectionPlan> */
    public function studentVisiblePlans(Reservation $reservation): Collection
    {
        return AzadSelectionPlan::query()
            ->where('reservation_id', $reservation->id)
            ->where('status', AzadSelectionPlan::STATUS_PUBLISHED)
            ->where('is_public_visible', true)
            ->with('items')
            ->orderByDesc('version')
            ->get();
    }

    public function studentVisiblePlanOrFail(Reservation $reservation, AzadSelectionPlan $plan): AzadSelectionPlan
    {
        return AzadSelectionPlan::query()
            ->whereKey($plan->id)
            ->where('reservation_id', $reservation->id)
            ->where('status', AzadSelectionPlan::STATUS_PUBLISHED)
            ->where('is_public_visible', true)
            ->firstOrFail();
    }

    /** @param list<int> $orderedIds */
    private function renumber(AzadSelectionPlan $plan, array $orderedIds): void
    {
        // Two passes so the (plan, priority) unique index never sees two rows on one number.
        AzadSelectionItem::query()->where('azad_selection_plan_id', $plan->id)
            ->update(['priority_order' => DB::raw('priority_order + '.(AzadSelectionPlan::MAX_ITEMS * 10))]);
        foreach (array_values($orderedIds) as $index => $id) {
            AzadSelectionItem::query()->whereKey($id)->where('azad_selection_plan_id', $plan->id)->update(['priority_order' => $index + 1]);
        }
    }

    private function keepPublicLinkAccessible(Reservation $reservation): void
    {
        $expiresAt = now()->addDays(30);
        $updates = [];

        if (! $reservation->public_token) {
            do {
                $token = Str::random(80);
            } while (Reservation::query()->where('public_token', $token)->exists());
            $updates['public_token'] = $token;
        }

        if (! $reservation->public_token_expires_at || $reservation->public_token_expires_at->lessThan($expiresAt)) {
            $updates['public_token_expires_at'] = $expiresAt;
        }

        if ($updates !== []) {
            $reservation->forceFill($updates)->save();
        }
    }

    private function assertEligible(Reservation $reservation): void
    {
        if (! in_array($reservation->status, [ReservationStatus::Confirmed, ReservationStatus::Completed], true)) {
            throw ValidationException::withMessages(['reservation' => 'انتخاب رشته فقط برای رزروهای تأییدشده یا انجام‌شده قابل ثبت است.']);
        }
    }

    private function assertEditable(AzadSelectionPlan $plan): void
    {
        if ($plan->isArchived()) {
            throw ValidationException::withMessages(['plan' => 'انتخاب رشته آرشیوشده قابل ویرایش نیست.']);
        }
    }
}

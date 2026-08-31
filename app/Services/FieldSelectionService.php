<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\FieldSelectionItem;
use App\Models\FieldSelectionPlan;
use App\Models\Reservation;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\StudyPrograms\StudyProgramQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FieldSelectionService
{
    private const ITEM_FIELDS = [
        'field_code',
        'field_name',
        'field_description',
        'city',
        'university_name',
        'university_type',
        'university_description',
    ];

    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly StudyProgramQuery $studyPrograms,
    ) {
    }

    public function createPlanForReservation(Reservation $reservation, User $user): FieldSelectionPlan
    {
        $this->assertEligible($reservation);

        return DB::transaction(function () use ($reservation, $user): FieldSelectionPlan {
            $latestVersion = (int) FieldSelectionPlan::query()
                ->where('reservation_id', $reservation->id)
                ->lockForUpdate()
                ->max('version');

            $plan = FieldSelectionPlan::query()->create([
                'reservation_id' => $reservation->id,
                'student_id' => $reservation->student_id,
                'version' => $latestVersion + 1,
                'status' => FieldSelectionPlan::STATUS_DRAFT,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->activityLog->log('field_selection_plan_created', $reservation, $user, null, ['plan_id' => $plan->id, 'version' => $plan->version]);

            return $plan;
        });
    }

    public function addItem(FieldSelectionPlan $plan, array $data, User $user): FieldSelectionItem
    {
        $this->assertDraft($plan);
        $data = $this->normalizeItemPayload($data);

        return DB::transaction(function () use ($plan, $data, $user): FieldSelectionItem {
            $count = FieldSelectionItem::query()->where('field_selection_plan_id', $plan->id)->lockForUpdate()->count();
            if ($count >= 150) {
                throw ValidationException::withMessages(['field_code' => 'حداکثر تعداد انتخاب‌ها ۱۵۰ مورد است.']);
            }

            if (FieldSelectionItem::query()
                ->where('field_selection_plan_id', $plan->id)
                ->where('field_code', $data['field_code'])
                ->exists()) {
                throw ValidationException::withMessages(['field_code' => 'این کد رشته قبلاً در لیست ثبت شده است.']);
            }

            $item = $plan->items()->create([...$data, 'priority_order' => $count + 1]);
            $plan->forceFill(['updated_by' => $user->id])->save();
            $this->activityLog->log('field_selection_item_added', $plan->reservation, $user, null, ['plan_id' => $plan->id, 'item_id' => $item->id]);

            return $item;
        });
    }

    public function updateItem(FieldSelectionItem $item, array $data, User $user): FieldSelectionItem
    {
        $item->loadMissing('plan.reservation');
        $this->assertDraft($item->plan);
        $data = $this->normalizeItemPayload($data);

        if ($item->field_code !== $data['field_code'] && FieldSelectionItem::query()
            ->where('field_selection_plan_id', $item->field_selection_plan_id)
            ->where('field_code', $data['field_code'])
            ->exists()) {
            throw ValidationException::withMessages(['field_code' => 'این کد رشته قبلاً در لیست ثبت شده است.']);
        }

        $old = $item->only(self::ITEM_FIELDS);
        $item->update($data);
        $item->plan->forceFill(['updated_by' => $user->id])->save();
        $this->activityLog->log('field_selection_item_updated', $item->plan->reservation, $user, $old, $item->only(self::ITEM_FIELDS));

        return $item;
    }

    public function bulkUpdateItems(FieldSelectionPlan $plan, array $items, User $user): void
    {
        $this->assertDraft($plan);

        DB::transaction(function () use ($plan, $items, $user): void {
            $existing = FieldSelectionItem::query()
                ->where('field_selection_plan_id', $plan->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $submittedIds = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($existing->keys()->map(fn ($id) => (int) $id)->sort()->values()->all() !== $submittedIds) {
                throw ValidationException::withMessages(['items' => 'اطلاعات انتخاب‌ها معتبر نیست.']);
            }

            if (collect($items)->pluck('field_code')->map(fn ($code) => trim($code))->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['items' => 'این کد رشته قبلاً در لیست ثبت شده است.']);
            }

            foreach ($items as $data) {
                $item = $existing->get((int) $data['id']);
                $changes = $this->normalizeItemPayload($data);

                if ($item->only(array_keys($changes)) === $changes) {
                    continue;
                }

                $old = $item->only(array_keys($changes));
                $item->update($changes);
                $this->activityLog->log('field_selection_item_updated', $plan->reservation, $user, $old, $changes);
            }

            $plan->forceFill(['updated_by' => $user->id])->save();
        });
    }

    public function addItemFromCatalog(FieldSelectionPlan $plan, int $catalogId, User $user): FieldSelectionItem
    {
        $catalogItem = $this->studyPrograms->base()
            ->whereKey($catalogId)
            ->firstOrFail();

        if (! $catalogItem->academicField?->name || ! $catalogItem->city?->name) {
            throw ValidationException::withMessages(['field_catalog_id' => 'اطلاعات رشتهمحل انتخاب‌شده کامل نیست.']);
        }

        return $this->addItem($plan, [
            'field_code' => $catalogItem->code,
            'field_name' => $catalogItem->academicField->name,
            'field_description' => $this->programDescription($catalogItem),
            'city' => $catalogItem->city->name,
            'university_name' => $this->programUniversityName($catalogItem),
            'university_type' => $this->programUniversityType($catalogItem),
        ], $user);
    }

    /**
     * @param  array{q?:string|null, province_id?:int|string|null, city_id?:int|string|null, course_type_id?:int|string|null}  $filters
     * @return Collection<int, array{id:int, field_code:string, field_name:string, field_description:?string, city:string, province:?string, institution:?string, university_name:?string, university_type:?string, university_description:?string, course_type:?string, exam_group:?string, booklet_source:?string, source_file:?string, booklet_page:?int, booklet_section:?string, capacity:?int}>
     */
    public function searchFieldCatalog(array $filters): Collection
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $hasStructuredFilter = filled($filters['province_id'] ?? null)
            || filled($filters['city_id'] ?? null)
            || filled($filters['course_type_id'] ?? null);

        if ($search === '' && ! $hasStructuredFilter) {
            return collect();
        }

        $programFilters = [
            'province_id' => $filters['province_id'] ?? null,
            'city_id' => $filters['city_id'] ?? null,
            'course_type_id' => $filters['course_type_id'] ?? null,
        ];

        return $this->studyPrograms->base(array_filter($programFilters, filled(...)))
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('code', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('raw_data', 'like', '%'.$search.'%')
                    ->orWhereHas('academicField', fn ($field) => $field->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('institution', fn ($institution) => $institution->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('city', fn ($city) => $city->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('courseType', fn ($type) => $type->where('name', 'like', '%'.$search.'%'));
            }))
            ->orderBy('code')
            ->limit(30)
            ->get()
            ->map(function (StudyProgram $program): array {
                $capacity = ((int) ($program->first_semester_capacity ?? 0)) + ((int) ($program->second_semester_capacity ?? 0));
                $universityType = $this->programUniversityType($program);
                $description = $this->programDescription($program);
                $universityName = $this->programUniversityName($program);

                return [
                    'id' => $program->id,
                    'field_code' => $program->code,
                    'field_name' => $program->academicField?->name,
                    'field_description' => $description,
                    'city' => $program->city?->name,
                    'province' => $program->province?->name,
                    'institution' => $program->institution?->name,
                    'university_name' => $universityName,
                    'university_type' => $universityType,
                    'university_description' => $description,
                    'course_type' => $universityType,
                    'exam_group' => $program->examGroup?->name,
                    'booklet_source' => $this->bookletSource($program),
                    'source_file' => $program->source_file,
                    'booklet_page' => $program->booklet_page,
                    'booklet_section' => $program->booklet_section,
                    'capacity' => $capacity > 0 ? $capacity : null,
                ];
            })
            ->filter(fn (array $field) => filled($field['field_code']) && filled($field['field_name']) && filled($field['city']))
            ->values();
    }

    public function deleteItem(FieldSelectionItem $item, User $user): void
    {
        $item->loadMissing('plan.reservation');
        $plan = $item->plan;
        $this->assertDraft($plan);

        DB::transaction(function () use ($item, $plan, $user): void {
            $item->delete();
            $this->normalizeOrders($plan);
            $plan->forceFill(['updated_by' => $user->id])->save();
            $this->activityLog->log('field_selection_item_deleted', $plan->reservation, $user, null, ['plan_id' => $plan->id, 'item_id' => $item->id]);
        });
    }

    public function reorderItems(FieldSelectionPlan $plan, array $orderedItemIds, User $user): void
    {
        $this->assertDraft($plan);

        DB::transaction(function () use ($plan, $orderedItemIds, $user): void {
            $items = FieldSelectionItem::query()->where('field_selection_plan_id', $plan->id)->lockForUpdate()->get();
            $actual = $items->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $submitted = collect($orderedItemIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

            if ($actual !== $submitted) {
                throw ValidationException::withMessages(['ordered_item_ids' => 'ترتیب انتخاب‌ها معتبر نیست.']);
            }

            FieldSelectionItem::query()
                ->whereIn('id', $orderedItemIds)
                ->update(['priority_order' => DB::raw('priority_order + 150')]);

            foreach (array_values($orderedItemIds) as $index => $id) {
                FieldSelectionItem::query()->whereKey($id)->update(['priority_order' => $index + 1]);
            }

            $plan->forceFill(['updated_by' => $user->id])->save();
            $this->activityLog->log('field_selection_reordered', $plan->reservation, $user, null, ['plan_id' => $plan->id]);
        });
    }

    public function publish(FieldSelectionPlan $plan, User $user): FieldSelectionPlan
    {
        $this->assertDraft($plan);

        return DB::transaction(function () use ($plan, $user): FieldSelectionPlan {
            FieldSelectionPlan::query()
                ->where('reservation_id', $plan->reservation_id)
                ->where('status', FieldSelectionPlan::STATUS_PUBLISHED)
                ->update(['status' => FieldSelectionPlan::STATUS_ARCHIVED, 'updated_at' => now()]);

            $plan->forceFill([
                'status' => FieldSelectionPlan::STATUS_PUBLISHED,
                'published_at' => now(),
                'updated_by' => $user->id,
            ])->save();

            $this->activityLog->log('field_selection_published', $plan->reservation, $user, null, ['plan_id' => $plan->id, 'version' => $plan->version]);
            $this->keepPublicLinkAccessibleForPublishedSelection($plan->reservation);

            return $plan;
        });
    }

    public function createNewVersionFromExisting(FieldSelectionPlan $plan, User $user): FieldSelectionPlan
    {
        if (! $plan->isPublished()) {
            return $plan;
        }

        return DB::transaction(function () use ($plan, $user): FieldSelectionPlan {
            $plan = FieldSelectionPlan::query()->with('items')->whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $plan->forceFill(['status' => FieldSelectionPlan::STATUS_ARCHIVED, 'updated_by' => $user->id])->save();

            $newPlan = FieldSelectionPlan::query()->create([
                'reservation_id' => $plan->reservation_id,
                'student_id' => $plan->student_id,
                'version' => ((int) FieldSelectionPlan::query()->where('reservation_id', $plan->reservation_id)->max('version')) + 1,
                'status' => FieldSelectionPlan::STATUS_DRAFT,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($plan->items as $item) {
                $newPlan->items()->create($item->only(['priority_order', ...self::ITEM_FIELDS]));
            }

            $this->activityLog->log('field_selection_new_version_created', $plan->reservation, $user, null, ['plan_id' => $newPlan->id, 'version' => $newPlan->version]);

            return $newPlan;
        });
    }

    private function normalizeItemPayload(array $data): array
    {
        return collect($data)
            ->only(self::ITEM_FIELDS)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
    }

    private function programUniversityType(StudyProgram $program): ?string
    {
        return $program->courseType?->name ?: $program->original_course_type;
    }

    private function programUniversityName(StudyProgram $program): ?string
    {
        $name = trim((string) ($program->institution?->name ?? ''));
        $campus = trim((string) ($program->institutionCampus?->name ?? ''));

        if ($name !== '' && $campus !== '' && $campus !== $name) {
            return $name.' - '.$campus;
        }

        return $name !== '' ? $name : ($campus !== '' ? $campus : null);
    }

    private function bookletSource(StudyProgram $program): ?string
    {
        if ($program->examGroup?->name) {
            return 'دفترچه '.$program->examGroup->name;
        }

        return filled($program->source_file) ? basename((string) $program->source_file) : null;
    }

    private function programDescription(StudyProgram $program): ?string
    {
        if (filled($program->description)) {
            return trim((string) $program->description);
        }

        $raw = is_array($program->raw_data) ? $program->raw_data : [];
        foreach (['field_description', 'program_description', 'university_description', 'description', 'توضیحات رشته', 'توضیحات کدرشته', 'توضیحات دانشگاه', 'توضیحات', 'توضيحات', 'شرح'] as $key) {
            if (filled($raw[$key] ?? null)) {
                return trim((string) $raw[$key]);
            }
        }

        return null;
    }

    private function keepPublicLinkAccessibleForPublishedSelection(Reservation $reservation): void
    {
        $expiresAt = now()->addDays(30);
        $updates = [];

        if (! $reservation->public_token) {
            $updates['public_token'] = $this->newPublicToken();
        }

        if (! $reservation->public_token_expires_at || $reservation->public_token_expires_at->lessThan($expiresAt)) {
            $updates['public_token_expires_at'] = $expiresAt;
        }

        if ($updates !== []) {
            $reservation->forceFill($updates)->save();
        }
    }

    private function newPublicToken(): string
    {
        do {
            $token = Str::random(80);
        } while (Reservation::query()->where('public_token', $token)->exists());

        return $token;
    }

    private function assertEligible(Reservation $reservation): void
    {
        if (! in_array($reservation->status, [ReservationStatus::Confirmed, ReservationStatus::Completed], true)) {
            throw ValidationException::withMessages(['reservation' => 'انتخاب رشته فقط برای رزروهای تأییدشده یا انجام‌شده قابل ثبت است.']);
        }
    }

    private function assertDraft(FieldSelectionPlan $plan): void
    {
        if (! $plan->isDraft()) {
            throw ValidationException::withMessages(['plan' => 'فقط نسخه پیش‌نویس قابل ویرایش است.']);
        }
    }

    private function normalizeOrders(FieldSelectionPlan $plan): void
    {
        $plan->items()->get()->each(fn (FieldSelectionItem $item, int $index) => $item->update(['priority_order' => $index + 1]));
    }
}

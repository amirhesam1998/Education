<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\FieldSelectionItem;
use App\Models\FieldSelectionPlan;
use App\Models\Reservation;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\StudyPrograms\StudyProgramQuery;
use App\Services\StudyPrograms\StudyProgramValueMapper;
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
        private readonly StudyProgramValueMapper $valueMapper,
    ) {
    }

    public function createPlanForReservation(Reservation $reservation, User $user): FieldSelectionPlan
    {
        $this->assertEligible($reservation);

        return DB::transaction(function () use ($reservation, $user): FieldSelectionPlan {
            $latestVersion = (int) FieldSelectionPlan::withTrashed()
                ->where('reservation_id', $reservation->id)
                ->lockForUpdate()
                ->max('version');

            $plan = FieldSelectionPlan::query()->create([
                'reservation_id' => $reservation->id,
                'student_id' => $reservation->student_id,
                'version' => $latestVersion + 1,
                'status' => FieldSelectionPlan::STATUS_DRAFT,
                'is_public_visible' => false,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->activityLog->log('field_selection_plan_created', $reservation, $user, null, $this->planActivityData($plan, ['version' => $plan->version]));

            return $plan;
        });
    }

    /** @return array<string, string> */
    public function examTypeOptions(Reservation $reservation): array
    {
        $reservation->loadMissing('student');

        return collect($reservation->student?->exam_type ?? [])
            ->filter(fn ($label) => filled($label))
            ->mapWithKeys(function (string $label): array {
                $key = $this->valueMapper->examGroupSlug($label) ?: 'exam_'.substr(hash('sha256', $label), 0, 12);

                return [$key => $label];
            })
            ->all();
    }

    public function getOrCreatePlanForExamType(Reservation $reservation, string $examTypeKey, User $user): FieldSelectionPlan
    {
        $this->assertEligible($reservation);
        $options = $this->examTypeOptions($reservation);
        if (! array_key_exists($examTypeKey, $options)) {
            throw ValidationException::withMessages(['exam_type_key' => 'نوع کنکور انتخاب‌شده برای این رزرو معتبر نیست.']);
        }

        return DB::transaction(function () use ($reservation, $examTypeKey, $user): FieldSelectionPlan {
            Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $plan = FieldSelectionPlan::query()
                ->where('reservation_id', $reservation->id)
                ->where('exam_type_key', $examTypeKey)
                ->where('status', '!=', FieldSelectionPlan::STATUS_ARCHIVED)
                ->lockForUpdate()
                ->first();

            if ($plan) {
                return $plan;
            }

            $version = ((int) FieldSelectionPlan::withTrashed()->where('reservation_id', $reservation->id)->max('version')) + 1;
            $plan = FieldSelectionPlan::query()->create([
                'reservation_id' => $reservation->id,
                'student_id' => $reservation->student_id,
                'exam_type_key' => $examTypeKey,
                'version' => $version,
                'status' => FieldSelectionPlan::STATUS_DRAFT,
                'is_public_visible' => false,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $this->activityLog->log('field_selection_exam_type_selected', $reservation, $user, null, ['plan_id' => $plan->id, 'exam_type_key' => $examTypeKey]);
            $this->activityLog->log('field_selection_plan_created', $reservation, $user, null, $this->planActivityData($plan));

            return $plan;
        });
    }

    public function deletePlan(FieldSelectionPlan $plan, User $user): void
    {
        $plan->forceFill(['is_public_visible' => false, 'student_hidden_at' => now(), 'visibility_changed_by' => $user->id, 'updated_by' => $user->id])->save();
        $plan->delete();
        $this->activityLog->log('field_selection_plan_deleted', $plan->reservation, $user, null, ['plan_id' => $plan->id, 'exam_type_key' => $plan->exam_type_key]);
    }

    /** @return Collection<string, Collection<int, FieldSelectionPlan>> */
    public function getVisiblePlansGroupedByExamType(Reservation $reservation): Collection
    {
        return $this->getStudentVisiblePlans($reservation)->groupBy(fn (FieldSelectionPlan $plan) => $plan->exam_type_key ?: 'legacy');
    }

    public function addItem(FieldSelectionPlan $plan, array $data, User $user): FieldSelectionItem
    {
        $this->assertEditable($plan);
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
            $this->activityLog->log('field_selection_item_added', $plan->reservation, $user, null, $this->planActivityData($plan, ['item_id' => $item->id]));

            return $item;
        });
    }

    public function updateItem(FieldSelectionItem $item, array $data, User $user): FieldSelectionItem
    {
        $item->loadMissing('plan.reservation');
        $this->assertEditable($item->plan);
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
        $this->activityLog->log('field_selection_item_updated', $item->plan->reservation, $user, $old, $this->planActivityData($item->plan, [...$item->only(self::ITEM_FIELDS), 'item_id' => $item->id]));

        return $item;
    }

    public function bulkUpdateItems(FieldSelectionPlan $plan, array $items, User $user): void
    {
        $this->assertEditable($plan);

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
                $this->activityLog->log('field_selection_item_updated', $plan->reservation, $user, $old, $this->planActivityData($plan, [...$changes, 'item_id' => $item->id]));
            }

            $plan->forceFill(['updated_by' => $user->id])->save();
            $this->activityLog->log('field_selection_plan_updated', $plan->reservation, $user, null, $this->planActivityData($plan));
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
            || filled($filters['course_type_id'] ?? null)
            || filled($filters['booklet'] ?? null)
            || filled($filters['semester'] ?? null)
            || filled($filters['academic_record_type'] ?? null)
            || filled($filters['gender'] ?? null)
            || filled($filters['province_ids'] ?? null);

        if ($search === '' && ! $hasStructuredFilter) {
            return collect();
        }

        $programFilters = [
            'province_id' => $filters['province_id'] ?? null,
            'city_id' => $filters['city_id'] ?? null,
            'course_type_id' => $filters['course_type_id'] ?? null,
            'province_ids' => $filters['province_ids'] ?? null,
            'booklet' => $filters['booklet'] ?? null,
            'semester' => $filters['semester'] ?? null,
            'academic_record_type' => $filters['academic_record_type'] ?? null,
            'gender' => $filters['gender'] ?? null,
        ];

        $query = $this->studyPrograms->base(array_filter($programFilters, filled(...)));
        if ($search !== '') {
            $this->studyPrograms->applySearch($query, $search);
        }

        return $query
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
        $this->assertEditable($plan);

        DB::transaction(function () use ($item, $plan, $user): void {
            $item->delete();
            $this->normalizeOrders($plan);
            $plan->forceFill(['updated_by' => $user->id])->save();
            $this->activityLog->log('field_selection_item_deleted', $plan->reservation, $user, null, $this->planActivityData($plan, ['item_id' => $item->id]));
        });
    }

    public function reorderItems(FieldSelectionPlan $plan, array $orderedItemIds, User $user): void
    {
        $this->assertEditable($plan);

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
            $this->activityLog->log('field_selection_reordered', $plan->reservation, $user, null, $this->planActivityData($plan));
        });
    }

    public function publish(FieldSelectionPlan $plan, User $user, bool $visibleToStudent = false): FieldSelectionPlan
    {
        if (! $plan->isDraft()) {
            throw ValidationException::withMessages(['plan' => 'فقط انتخاب رشته پیش‌نویس قابل انتشار است.']);
        }

        return DB::transaction(function () use ($plan, $user, $visibleToStudent): FieldSelectionPlan {
            $plan->forceFill([
                'status' => FieldSelectionPlan::STATUS_PUBLISHED,
                'published_at' => now(),
                'is_public_visible' => false,
                'updated_by' => $user->id,
            ])->save();

            $this->activityLog->log('field_selection_published', $plan->reservation, $user, null, ['plan_id' => $plan->id, 'version' => $plan->version]);
            if ($visibleToStudent) {
                $this->setPublicVisibility($plan, true, $user);
            }
            $this->keepPublicLinkAccessibleForPublishedSelection($plan->reservation);

            return $plan;
        });
    }

    public function setPublicVisibility(FieldSelectionPlan $plan, bool $visible, User $user): FieldSelectionPlan
    {
        if ($visible && ! $plan->canBePubliclyVisible()) {
            throw ValidationException::withMessages(['plan' => 'فقط نسخه‌های منتشرشده برای دانش‌آموز قابل نمایش هستند.']);
        }

        $plan->forceFill([
            'is_public_visible' => $visible,
            'student_visible_at' => $visible ? now() : null,
            'student_hidden_at' => $visible ? null : now(),
            'visibility_changed_by' => $user->id,
            'visibility_note' => null,
            'updated_by' => $user->id,
        ])->save();

        $this->activityLog->log($visible ? 'field_selection_plan_shown_to_student' : 'field_selection_plan_hidden_from_student', $plan->reservation, $user, null, ['plan_id' => $plan->id, 'version' => $plan->version]);

        if ($visible) {
            $this->keepPublicLinkAccessibleForPublishedSelection($plan->reservation);
        }

        return $plan;
    }

    public function showToStudent(FieldSelectionPlan $plan, User $user, ?string $note = null): FieldSelectionPlan
    {
        $this->setPublicVisibility($plan, true, $user);
        $plan->forceFill(['visibility_note' => $note])->save();

        return $plan;
    }

    public function hideFromStudent(FieldSelectionPlan $plan, User $user, ?string $note = null): FieldSelectionPlan
    {
        $this->setPublicVisibility($plan, false, $user);
        $plan->forceFill(['visibility_note' => $note])->save();

        return $plan;
    }

    public function archive(FieldSelectionPlan $plan, User $user): FieldSelectionPlan
    {
        if ($plan->status === FieldSelectionPlan::STATUS_ARCHIVED) {
            return $plan;
        }

        $plan->forceFill([
            'status' => FieldSelectionPlan::STATUS_ARCHIVED,
            'is_public_visible' => false,
            'student_hidden_at' => now(),
            'visibility_changed_by' => $user->id,
            'updated_by' => $user->id,
        ])->save();
        $this->activityLog->log('field_selection_archived', $plan->reservation, $user, null, ['plan_id' => $plan->id, 'version' => $plan->version]);

        return $plan;
    }

    /** @return Collection<int, FieldSelectionPlan> */
    public function getStudentVisiblePlans(Reservation $reservation): Collection
    {
        return FieldSelectionPlan::query()
            ->where('reservation_id', $reservation->id)
            ->where('status', FieldSelectionPlan::STATUS_PUBLISHED)
            ->where('is_public_visible', true)
            ->with(['items' => fn ($query) => $query->orderBy('priority_order')])
            ->orderByDesc('version')
            ->get();
    }

    public function getStudentVisiblePlanOrFail(Reservation $reservation, FieldSelectionPlan $plan): FieldSelectionPlan
    {
        return FieldSelectionPlan::query()
            ->whereKey($plan->id)
            ->where('reservation_id', $reservation->id)
            ->where('status', FieldSelectionPlan::STATUS_PUBLISHED)
            ->where('is_public_visible', true)
            ->firstOrFail();
    }

    public function assertPlanIsStudentVisible(FieldSelectionPlan $plan, Reservation $reservation): void
    {
        $this->getStudentVisiblePlanOrFail($reservation, $plan);
    }

    public function createNewVersionFromExisting(FieldSelectionPlan $plan, User $user): FieldSelectionPlan
    {
        if (! $plan->isPublished()) {
            return $plan;
        }

        return DB::transaction(function () use ($plan, $user): FieldSelectionPlan {
            $plan = FieldSelectionPlan::query()->with('items')->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            $newPlan = FieldSelectionPlan::query()->create([
                'reservation_id' => $plan->reservation_id,
                'student_id' => $plan->student_id,
                'version' => ((int) FieldSelectionPlan::withTrashed()->where('reservation_id', $plan->reservation_id)->max('version')) + 1,
                'status' => FieldSelectionPlan::STATUS_DRAFT,
                'is_public_visible' => false,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($plan->items as $item) {
                $newPlan->items()->create($item->only(['priority_order', ...self::ITEM_FIELDS]));
            }

            $this->activityLog->log('field_selection_new_version_created', $plan->reservation, $user, null, $this->planActivityData($newPlan, ['version' => $newPlan->version]));

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

    /** @return array<string, mixed> */
    private function planActivityData(FieldSelectionPlan $plan, array $data = []): array
    {
        return [...$data, 'plan_id' => $plan->id, 'exam_type_key' => $plan->exam_type_key];
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

    private function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
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

    private function assertEditable(FieldSelectionPlan $plan): void
    {
        if ($plan->status === FieldSelectionPlan::STATUS_ARCHIVED) {
            throw ValidationException::withMessages(['plan' => 'انتخاب رشته آرشیوشده قابل ویرایش نیست.']);
        }
    }

    private function normalizeOrders(FieldSelectionPlan $plan): void
    {
        $plan->items()->get()->each(fn (FieldSelectionItem $item, int $index) => $item->update(['priority_order' => $index + 1]));
    }
}

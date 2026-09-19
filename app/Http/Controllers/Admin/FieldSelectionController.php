<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderFieldSelectionItemsRequest;
use App\Http\Requests\Admin\AddFieldSelectionCatalogItemRequest;
use App\Http\Requests\Admin\BulkUpdateFieldSelectionItemsRequest;
use App\Http\Requests\Admin\SearchFieldCatalogRequest;
use App\Http\Requests\Admin\StoreFieldSelectionItemRequest;
use App\Http\Requests\Admin\SelectFieldSelectionExamTypeRequest;
use App\Http\Requests\Admin\UpdateFieldSelectionItemRequest;
use App\Models\City;
use App\Models\CourseType;
use App\Models\FieldSelectionItem;
use App\Models\FieldSelectionPlan;
use App\Models\Province;
use App\Models\Reservation;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\FieldSelectionService;
use App\Services\StudentPrivacyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FieldSelectionController extends Controller
{
    public function createPlan(Reservation $reservation, SelectFieldSelectionExamTypeRequest $request, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $reservation);
        $plan = $fieldSelections->getOrCreatePlanForExamType($reservation, $request->validated('exam_type_key'), $request->user());

        return redirect()->route('admin.reservations.field-selection.show', [$reservation, 'exam_type' => $plan->exam_type_key]);
    }

    public function show(Reservation $reservation, Request $request, FieldSelectionService $fieldSelections): View
    {
        $this->ensureViewer($request->user(), $reservation);
        $examTypeOptions = $fieldSelections->examTypeOptions($reservation);
        $selectedExamType = $request->string('exam_type')->toString();

        if ($request->filled('plan')) {
            $legacyPlan = FieldSelectionPlan::query()->where('reservation_id', $reservation->id)->findOrFail($request->integer('plan'));
            $selectedExamType = (string) $legacyPlan->exam_type_key;
            if ($selectedExamType === '') {
                $legacyPlan->load(['items', 'creator', 'updater', 'reservation.slot.advisor', 'reservation.advisor']);

                return view('admin.field-selection.show', [
                    'plan' => $legacyPlan,
                    'reservation' => $reservation,
                    'examTypeOptions' => [],
                    'selectedExamType' => '',
                    'selectedExamTypeLabel' => 'انتخاب رشته ثبت‌شده',
                    'canViewStudentPersonalInfo' => $this->canViewStudentPersonalInfo($request->user()),
                    ...$this->catalogFilterData(),
                ]);
            }
        }

        if ($selectedExamType === '' && count($examTypeOptions) === 1) {
            $selectedExamType = (string) array_key_first($examTypeOptions);
        }

        if ($selectedExamType === '') {
            return view('admin.field-selection.select-exam', compact('reservation', 'examTypeOptions'));
        }

        $plan = $fieldSelections->getOrCreatePlanForExamType($reservation, $selectedExamType, $request->user());
        $plan->load(['items', 'creator', 'updater', 'reservation.slot.advisor', 'reservation.advisor']);

        return view('admin.field-selection.show', [
            'plan' => $plan,
            'reservation' => $reservation,
            'examTypeOptions' => $examTypeOptions,
            'selectedExamType' => $selectedExamType,
            'selectedExamTypeLabel' => $examTypeOptions[$selectedExamType] ?? $selectedExamType,
            'canViewStudentPersonalInfo' => $this->canViewStudentPersonalInfo($request->user()),
            ...$this->catalogFilterData(),
        ]);
    }

    public function addItem(StoreFieldSelectionItemRequest $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse|JsonResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $item = $fieldSelections->addItem($plan, $request->validated(), $request->user());

        if ($request->expectsJson()) {
            return $this->itemJsonResponse($item, 'رشته با موفقیت به لیست اضافه شد.');
        }

        return back()->with('success', 'رشته موردنظر ثبت شد.');
    }

    public function addItemFromCatalog(AddFieldSelectionCatalogItemRequest $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): JsonResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $item = $fieldSelections->addItemFromCatalog($plan, $request->validated('field_catalog_id'), $request->user());

        return $this->itemJsonResponse($item, 'رشته با موفقیت به لیست اضافه شد.');
    }

    public function updateItem(UpdateFieldSelectionItemRequest $request, FieldSelectionItem $item, FieldSelectionService $fieldSelections): RedirectResponse|JsonResponse
    {
        $item->loadMissing('plan.reservation');
        $this->ensureManager($request->user(), $item->plan->reservation);
        $fieldSelections->updateItem($item, $request->validated(), $request->user());

        if ($request->expectsJson()) {
            return $this->itemJsonResponse($item, 'تغییرات با موفقیت ذخیره شد.');
        }

        return back()->with('success', 'رشته موردنظر ویرایش شد.');
    }

    public function bulkUpdate(BulkUpdateFieldSelectionItemsRequest $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse|JsonResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $fieldSelections->bulkUpdateItems($plan, $request->validated('items'), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'تغییرات با موفقیت ذخیره شد.']);
        }

        return back()->with('success', 'تغییرات با موفقیت ذخیره شد.');
    }

    public function searchFields(SearchFieldCatalogRequest $request, FieldSelectionService $fieldSelections): JsonResponse
    {
        $items = $fieldSelections->searchFieldCatalog($request->validated());

        return response()->json([
            'success' => true,
            'count' => $items->count(),
            'items' => $items->values(),
        ]);
    }

    public function filterCities(Request $request): JsonResponse
    {
        $provinceId = $request->integer('province_id');

        if (! $provinceId) {
            return response()->json([]);
        }

        $programIds = StudyProgram::query()
            ->select('city_id')
            ->whereNotNull('city_id')
            ->where('validation_status', 'validated')
            ->where('province_id', $provinceId)
            ->when($request->integer('course_type_id'), fn ($query, $id) => $query->where('course_type_id', $id));

        $search = trim($request->string('q')->toString());
        $normalizedSearch = $this->normalizeDigits($search);

        if ($search !== '' && preg_match('/^\d+$/', $normalizedSearch)) {
            $programIds->where('code', $normalizedSearch);
        } elseif ($search !== '') {
            $fieldMatchQuery = (clone $programIds)
                ->whereHas('academicField', fn ($field) => $field->where('name', 'like', '%'.$search.'%'));

            $programIds = $fieldMatchQuery->exists()
                ? $fieldMatchQuery
                : $programIds->where(function ($nested) use ($search): void {
                    $nested->whereHas('academicField', fn ($field) => $field->where('name', 'like', '%'.$search.'%'))
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('institution', fn ($institution) => $institution->where('name', 'like', '%'.$search.'%'));
                });
        }

        return response()->json(City::query()
            ->where('province_id', $provinceId)
            ->whereIn('id', $programIds)
            ->orderBy('normalized_name')
            ->get(['id', 'name']));
    }

    public function deleteItem(Request $request, FieldSelectionItem $item, FieldSelectionService $fieldSelections): RedirectResponse|JsonResponse
    {
        $item->loadMissing('plan.reservation');
        $this->ensureManager($request->user(), $item->plan->reservation);
        $fieldSelections->deleteItem($item, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'رشته با موفقیت حذف شد.', 'id' => $item->id, 'count' => $item->plan->items()->count()]);
        }

        return back()->with('success', 'رشته موردنظر حذف شد.');
    }

    public function reorder(ReorderFieldSelectionItemsRequest $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse|JsonResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $fieldSelections->reorderItems($plan, $request->validated('ordered_item_ids'), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'ترتیب با موفقیت ذخیره شد.']);
        }

        return back()->with('success', 'ترتیب انتخاب‌ها ذخیره شد.');
    }

    public function publish(Request $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $fieldSelections->publish($plan, $request->user(), $request->boolean('visible_to_student'));

        return redirect()->route('admin.reservations.show', $plan->reservation)->with('success', 'لیست انتخاب رشته منتشر شد.');
    }

    public function newVersion(Request $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $newPlan = $fieldSelections->createNewVersionFromExisting($plan, $request->user());

        return redirect()->route('admin.reservations.field-selection.show', [$plan->reservation, 'plan' => $newPlan->id]);
    }

    public function publicVisibility(Request $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $fieldSelections->setPublicVisibility($plan, $request->boolean('is_public_visible'), $request->user());

        return back()->with('success', $request->boolean('is_public_visible') ? 'نسخه برای دانش‌آموز قابل مشاهده شد.' : 'نسخه از دید دانش‌آموز مخفی شد.');
    }

    public function showToStudent(Request $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $fieldSelections->showToStudent($plan, $request->user(), $request->validate(['visibility_note' => ['nullable', 'string', 'max:1000']])['visibility_note'] ?? null);

        return back()->with('success', 'نسخه برای دانش‌آموز قابل مشاهده شد.');
    }

    public function hideFromStudent(Request $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $fieldSelections->hideFromStudent($plan, $request->user(), $request->validate(['visibility_note' => ['nullable', 'string', 'max:1000']])['visibility_note'] ?? null);

        return back()->with('success', 'نسخه از دید دانش‌آموز مخفی شد.');
    }

    public function archive(Request $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $fieldSelections->archive($plan, $request->user());

        return back()->with('success', 'نسخه آرشیو شد.');
    }

    public function destroyPlan(Request $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $reservation = $plan->reservation;
        $fieldSelections->deletePlan($plan, $request->user());

        return redirect()->route('admin.reservations.field-selection.show', $reservation)->with('success', 'انتخاب رشته حذف شد.');
    }

    public function print(Request $request, FieldSelectionPlan $plan): View
    {
        $plan->load(['items', 'student', 'reservation.slot.advisor', 'reservation.advisor']);
        $this->ensureViewer($request->user(), $plan->reservation);
        $canViewStudentPersonalInfo = $this->canViewStudentPersonalInfo($request->user());
        $selectedExamTypeLabel = app(FieldSelectionService::class)->examTypeOptions($plan->reservation)[$plan->exam_type_key] ?? 'انتخاب رشته ثبت‌شده';

        return view('field-selection.print', compact('plan', 'canViewStudentPersonalInfo', 'selectedExamTypeLabel'));
    }

    private function ensureViewer(User $user, Reservation $reservation): void
    {
        abort_unless(! $user->hasRole(User::ROLE_CONSULTANT) || $reservation->advisor?->user_id === $user->id, 403);
    }

    private function canViewStudentPersonalInfo(User $user): bool
    {
        return app(StudentPrivacyService::class)->canViewPersonalData($user);
    }

    private function catalogFilterData(): array
    {
        return [
            'catalogProvinces' => Province::query()
                ->whereIn('id', StudyProgram::query()->select('province_id')->whereNotNull('province_id')->where('validation_status', 'validated'))
                ->orderBy('normalized_name')
                ->get(['id', 'name']),
            'catalogCourseTypes' => CourseType::query()
                ->whereIn('id', StudyProgram::query()->select('course_type_id')->whereNotNull('course_type_id')->where('validation_status', 'validated'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'catalogBooklets' => StudyProgram::query()
                ->where('validation_status', 'validated')
                ->where('is_active', true)
                ->whereNotNull('source_file')
                ->distinct()
                ->orderBy('source_file')
                ->pluck('source_file'),
        ];
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

    private function ensureManager(User $user, Reservation $reservation): void
    {
        $this->ensureViewer($user, $reservation);
    }

    private function itemJsonResponse(FieldSelectionItem $item, string $message): JsonResponse
    {
        $item->refresh();

        return response()->json([
            'success' => true,
            'message' => $message,
            'item' => $item->only(['id', 'priority_order', 'field_code', 'field_name', 'field_description', 'city', 'university_name', 'university_type', 'university_description']),
            'count' => $item->plan->items()->count(),
            'html' => view('admin.field-selection._item', ['item' => $item, 'editable' => true])->render(),
        ]);
    }
}

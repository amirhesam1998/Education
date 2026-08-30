<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderFieldSelectionItemsRequest;
use App\Http\Requests\Admin\AddFieldSelectionCatalogItemRequest;
use App\Http\Requests\Admin\BulkUpdateFieldSelectionItemsRequest;
use App\Http\Requests\Admin\SearchFieldCatalogRequest;
use App\Http\Requests\Admin\StoreFieldSelectionItemRequest;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FieldSelectionController extends Controller
{
    public function createPlan(Reservation $reservation, Request $request, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $reservation);
        $plan = $fieldSelections->createPlanForReservation($reservation, $request->user());

        return redirect()->route('admin.reservations.field-selection.show', [$reservation, 'plan' => $plan->id]);
    }

    public function show(Reservation $reservation, Request $request): View
    {
        $this->ensureViewer($request->user(), $reservation);
        $plan = $request->filled('plan')
            ? FieldSelectionPlan::query()->where('reservation_id', $reservation->id)->findOrFail($request->integer('plan'))
            : ($reservation->fieldSelectionPlans()->where('status', FieldSelectionPlan::STATUS_DRAFT)->first()
                ?: $reservation->fieldSelectionPlans()->where('status', FieldSelectionPlan::STATUS_PUBLISHED)->first());

        abort_unless($plan, 404);
        $plan->load(['items', 'creator', 'updater', 'reservation.student.phones', 'reservation.slot.advisor', 'reservation.advisor']);

        return view('admin.field-selection.show', [
            'plan' => $plan,
            'reservation' => $reservation,
            'versions' => $reservation->fieldSelectionPlans()->with('creator')->get(),
            'catalogProvinces' => Province::query()
                ->whereIn('id', StudyProgram::query()->select('province_id')->whereNotNull('province_id')->where('validation_status', 'validated'))
                ->orderBy('normalized_name')
                ->get(['id', 'name']),
            'catalogCourseTypes' => CourseType::query()
                ->whereIn('id', StudyProgram::query()->select('course_type_id')->whereNotNull('course_type_id')->where('validation_status', 'validated'))
                ->orderBy('name')
                ->get(['id', 'name']),
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
            return response()->json(['success' => true, 'message' => 'تغییرات با موفقیت ذخیره شد.', 'item' => $item->fresh()]);
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
        return response()->json($fieldSelections->searchFieldCatalog($request->validated()));
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
            ->when($request->integer('course_type_id'), fn ($query, $id) => $query->where('course_type_id', $id))
            ->when(trim($request->string('q')->toString()) !== '', function ($query) use ($request): void {
                $search = trim($request->string('q')->toString());
                $query->where(function ($nested) use ($search): void {
                    $nested->where('code', 'like', '%'.$search.'%')
                        ->orWhereHas('academicField', fn ($field) => $field->where('name', 'like', '%'.$search.'%'));
                });
            });

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
        $fieldSelections->publish($plan, $request->user());

        return redirect()->route('admin.reservations.show', $plan->reservation)->with('success', 'لیست انتخاب رشته منتشر شد.');
    }

    public function newVersion(Request $request, FieldSelectionPlan $plan, FieldSelectionService $fieldSelections): RedirectResponse
    {
        $this->ensureManager($request->user(), $plan->reservation);
        $newPlan = $fieldSelections->createNewVersionFromExisting($plan, $request->user());

        return redirect()->route('admin.reservations.field-selection.show', [$plan->reservation, 'plan' => $newPlan->id]);
    }

    public function print(Request $request, FieldSelectionPlan $plan): View
    {
        $plan->load(['items', 'student', 'reservation.slot.advisor', 'reservation.advisor']);
        $this->ensureViewer($request->user(), $plan->reservation);

        return view('field-selection.print', compact('plan'));
    }

    private function ensureViewer(User $user, Reservation $reservation): void
    {
        abort_unless(! $user->hasRole(User::ROLE_CONSULTANT) || $reservation->advisor?->user_id === $user->id, 403);
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
            'item' => $item->only(['id', 'priority_order', 'field_code', 'field_name', 'city']),
            'count' => $item->plan->items()->count(),
            'html' => view('admin.field-selection._item', ['item' => $item, 'editable' => true])->render(),
        ]);
    }
}

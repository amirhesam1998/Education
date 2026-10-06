<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AzadProgram;
use App\Models\AzadSelectionItem;
use App\Models\AzadSelectionPlan;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Azad\AzadProgramQuery;
use App\Services\Azad\AzadSelectionService;
use App\Services\StudentPrivacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** «مدیریت انتخاب رشته آزاد»: a reservation's Azad university list. */
class AzadSelectionController extends Controller
{
    private const SEARCH_LIMIT = 300;

    public function show(Reservation $reservation, Request $request, AzadSelectionService $selections, AzadProgramQuery $query): View
    {
        $this->ensureViewer($request->user(), $reservation);
        $plan = $selections->getOrCreatePlan($reservation, $request->user());
        $plan->load(['items', 'reservation.student']);

        return view('admin.azad-selection.show', [
            'plan' => $plan,
            'reservation' => $reservation,
            'provinces' => $query->provinces(),
            'editable' => ! $plan->isArchived() && $request->user()->can('manage_azad_field_selection'),
        ]);
    }

    /** Programmes with a unit code + field code, for the add-by-code form. */
    public function lookup(Request $request, AzadSelectionService $selections): JsonResponse
    {
        $data = $request->validate([
            'unit_code' => ['required', 'string', 'max:10'],
            'field_code' => ['required', 'string', 'max:10'],
            'booklet' => ['nullable', Rule::in(array_keys(AzadProgram::BOOKLETS))],
        ]);

        $items = $selections->lookup($data['unit_code'], $data['field_code'], $data['booklet'] ?? null);

        return response()->json(['count' => $items->count(), 'items' => $items->map($this->programJson(...))->values()]);
    }

    public function search(Request $request, AzadProgramQuery $query): JsonResponse
    {
        $filters = $request->only(AzadProgramQuery::FILTERS);
        $filters['is_active'] = '1';
        $structured = collect($filters)->except(['is_active', 'year'])->filter(fn ($v) => filled($v));

        if ($structured->isEmpty() || (array_keys($structured->all()) === ['q'] && mb_strlen(trim((string) $filters['q'])) < 2)) {
            return response()->json(['count' => 0, 'total' => 0, 'items' => []]);
        }

        $builder = $query->apply(AzadProgram::query(), $filters);
        $total = (clone $builder)->count();
        $items = $builder->orderBy('province')->orderBy('city')->orderBy('unit_code')->orderBy('booklet')->orderBy('field_code')->orderBy('part_time')
            ->limit(self::SEARCH_LIMIT)->get();

        return response()->json(['count' => $items->count(), 'total' => $total, 'items' => $items->map($this->programJson(...))->values()]);
    }

    public function addItem(Request $request, AzadSelectionPlan $plan, AzadSelectionService $selections): JsonResponse|RedirectResponse
    {
        $this->ensureViewer($request->user(), $plan->reservation);
        $data = $request->validate([
            'azad_program_id' => ['required', 'integer', 'exists:azad_programs,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $item = $selections->addProgram($plan, AzadProgram::query()->findOrFail($data['azad_program_id']), $request->user(), $data['note'] ?? null);

        if ($request->expectsJson()) {
            return $this->itemJson($item, 'رشته‌محل به لیست اضافه شد.');
        }

        return back()->with('success', 'رشته‌محل به لیست اضافه شد.');
    }

    public function updateItem(Request $request, AzadSelectionItem $item, AzadSelectionService $selections): JsonResponse|RedirectResponse
    {
        $item->loadMissing('plan.reservation');
        $this->ensureViewer($request->user(), $item->plan->reservation);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $selections->updateNote($item, $data['note'] ?? null, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'توضیح ذخیره شد.']);
        }

        return back()->with('success', 'توضیح ذخیره شد.');
    }

    public function deleteItem(Request $request, AzadSelectionItem $item, AzadSelectionService $selections): JsonResponse|RedirectResponse
    {
        $item->loadMissing('plan.reservation');
        $this->ensureViewer($request->user(), $item->plan->reservation);
        $selections->deleteItem($item, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'رشته‌محل حذف شد.', 'id' => $item->id, 'count' => $item->plan->items()->count()]);
        }

        return back()->with('success', 'رشته‌محل حذف شد.');
    }

    public function reorder(Request $request, AzadSelectionPlan $plan, AzadSelectionService $selections): JsonResponse|RedirectResponse
    {
        $this->ensureViewer($request->user(), $plan->reservation);
        $data = $request->validate([
            'ordered_item_ids' => ['required', 'array', 'max:'.AzadSelectionPlan::MAX_ITEMS],
            'ordered_item_ids.*' => ['integer'],
        ]);
        $selections->reorder($plan, $data['ordered_item_ids'], $request->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'ترتیب ذخیره شد.']);
        }

        return back()->with('success', 'ترتیب ذخیره شد.');
    }

    public function publish(Request $request, AzadSelectionPlan $plan, AzadSelectionService $selections): RedirectResponse
    {
        $this->ensureViewer($request->user(), $plan->reservation);
        $selections->publish($plan, $request->user(), $request->boolean('visible_to_student'));

        return back()->with('success', 'لیست انتخاب رشته آزاد منتشر شد.');
    }

    public function showToStudent(Request $request, AzadSelectionPlan $plan, AzadSelectionService $selections): RedirectResponse
    {
        $this->ensureViewer($request->user(), $plan->reservation);
        $selections->setVisibility($plan, true, $request->user());

        return back()->with('success', 'انتخاب رشته آزاد برای دانش‌آموز قابل مشاهده شد.');
    }

    public function hideFromStudent(Request $request, AzadSelectionPlan $plan, AzadSelectionService $selections): RedirectResponse
    {
        $this->ensureViewer($request->user(), $plan->reservation);
        $selections->setVisibility($plan, false, $request->user());

        return back()->with('success', 'انتخاب رشته آزاد از دید دانش‌آموز مخفی شد.');
    }

    public function archive(Request $request, AzadSelectionPlan $plan, AzadSelectionService $selections): RedirectResponse
    {
        $this->ensureViewer($request->user(), $plan->reservation);
        $selections->archive($plan, $request->user());

        return redirect()->route('admin.reservations.show', $plan->reservation)->with('success', 'انتخاب رشته آزاد آرشیو شد.');
    }

    public function destroyPlan(Request $request, AzadSelectionPlan $plan, AzadSelectionService $selections): RedirectResponse
    {
        $this->ensureViewer($request->user(), $plan->reservation);
        $reservation = $plan->reservation;
        $selections->deletePlan($plan, $request->user());

        return redirect()->route('admin.reservations.show', $reservation)->with('success', 'انتخاب رشته آزاد حذف شد.');
    }

    public function print(Request $request, AzadSelectionPlan $plan): View
    {
        $plan->load(['items', 'student', 'reservation.slot.advisor', 'reservation.advisor']);
        $this->ensureViewer($request->user(), $plan->reservation);

        return view('azad-selection.print', [
            'plan' => $plan,
            'canViewStudentPersonalInfo' => app(StudentPrivacyService::class)->canViewPersonalData($request->user()),
            'autoPrint' => true,
        ]);
    }

    private function ensureViewer(User $user, Reservation $reservation): void
    {
        abort_unless(! $user->hasRole(User::ROLE_CONSULTANT) || $reservation->advisor?->user_id === $user->id, 403);
    }

    private function programJson(AzadProgram $p): array
    {
        return [
            'id' => $p->id,
            'year' => $p->year,
            'booklet' => $p->booklet,
            'booklet_label' => $p->booklet_label,
            'admission' => $p->admission,
            'level' => $p->level,
            'province' => $p->province,
            'city' => $p->city,
            'unit_code' => $p->unit_code,
            'unit_name' => $p->unit_name,
            'self_funded' => $p->self_funded,
            'field_code' => $p->field_code,
            'field_name' => $p->field_name,
            'part_time' => $p->part_time,
            'gender' => $p->gender,
            'exam_group' => AzadProgram::groupLabel($p->exam_group),
            'education_group' => AzadProgram::groupLabel($p->education_group),
            'capacity_first' => $p->capacity_first,
            'capacity_second' => $p->capacity_second,
            'page' => $p->booklet_page,
        ];
    }

    private function itemJson(AzadSelectionItem $item, string $message): JsonResponse
    {
        $item->refresh();

        return response()->json([
            'success' => true,
            'message' => $message,
            'count' => $item->plan->items()->count(),
            'html' => view('admin.azad-selection._item', ['item' => $item, 'editable' => true])->render(),
        ]);
    }
}

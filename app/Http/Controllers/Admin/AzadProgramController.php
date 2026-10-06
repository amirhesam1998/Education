<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AzadProgramRequest;
use App\Models\AzadProgram;
use App\Models\AzadProgramImport;
use App\Services\Azad\AzadProgramQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The Islamic Azad University catalogue (رشته‌محل آزاد), apart from the state one. */
class AzadProgramController extends Controller
{
    public function index(Request $request, AzadProgramQuery $query): View
    {
        $filters = $request->only(AzadProgramQuery::FILTERS) + ['is_active' => '1'];
        $programs = $query->apply(AzadProgram::query(), $filters)
            ->orderByDesc('year')
            ->orderBy('booklet')
            ->orderBy('province')
            ->orderBy('unit_code')
            ->orderBy('field_code')
            ->orderBy('part_time')
            ->paginate(50)
            ->withQueryString();

        return view('admin.azad-programs.index', [
            'programs' => $programs,
            'filters' => $filters,
            'years' => $query->years(),
            'provinces' => $query->provinces(),
            'cities' => filled($filters['province'] ?? null) ? $query->cities($filters['province']) : collect(),
            'lastImport' => AzadProgramImport::query()->where('status', 'completed')->latest('id')->first(),
        ]);
    }

    public function cities(Request $request, AzadProgramQuery $query): JsonResponse
    {
        $province = trim($request->string('province')->toString());

        return response()->json($province === '' ? [] : $query->cities($province, $request->only(AzadProgramQuery::FILTERS)));
    }

    public function units(Request $request, AzadProgramQuery $query): JsonResponse
    {
        return response()->json($query->units($request->only(AzadProgramQuery::FILTERS)));
    }

    public function create(AzadProgramQuery $query): View
    {
        return view('admin.azad-programs.form', [
            'program' => new AzadProgram(['year' => $query->years()->first() ?? 1405, 'gender' => 'زن و مرد', 'is_active' => true]),
            'provinces' => $query->provinces(),
        ]);
    }

    public function store(AzadProgramRequest $request): RedirectResponse
    {
        $program = AzadProgram::query()->create($this->attributes($request) + [
            'source_type' => AzadProgram::SOURCE_MANUAL,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.azad-programs.edit', $program)->with('success', 'رشته‌محل آزاد ثبت شد.');
    }

    public function edit(AzadProgram $azadProgram, AzadProgramQuery $query): View
    {
        return view('admin.azad-programs.form', ['program' => $azadProgram, 'provinces' => $query->provinces()]);
    }

    public function update(AzadProgramRequest $request, AzadProgram $azadProgram): RedirectResponse
    {
        // An edited booklet row is the admin's from now on: a later import leaves it alone.
        $azadProgram->update($this->attributes($request) + [
            'source_type' => AzadProgram::SOURCE_MANUAL,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'رشته‌محل آزاد ویرایش شد.');
    }

    public function destroy(AzadProgram $azadProgram): RedirectResponse
    {
        // Students' lists keep their own copy of the programme (azad_selection_items).
        $azadProgram->delete();

        return redirect()->route('admin.azad-programs.index')->with('success', 'رشته‌محل آزاد حذف شد.');
    }

    private function attributes(AzadProgramRequest $request): array
    {
        $data = $request->validated();
        [, $admission, $level] = AzadProgram::BOOKLETS[$data['booklet']];
        $data['admission'] = $admission;
        $data['level'] = $level;
        foreach (['province', 'city', 'unit_name', 'field_name'] as $key) {
            $data[$key] = trim((string) preg_replace('/\s+/u', ' ', strtr($data[$key], ['ي' => 'ی', 'ك' => 'ک'])));
        }
        if ($admission !== 'exam') {
            $data['exam_group'] = null;
            $data['capacity_first'] = null;
            $data['capacity_second'] = null;
        }
        $data['search_text'] = AzadProgram::searchText($data);

        return $data;
    }
}

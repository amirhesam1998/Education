<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionType;
use App\Models\City;
use App\Models\CourseType;
use App\Models\ExamGroup;
use App\Models\ExamYear;
use App\Models\Province;
use App\Models\StudyProgram;
use App\Models\StudyProgramImport;
use App\Models\StudyProgramReviewRecord;
use App\Services\StudyPrograms\StudyProgramQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudyProgramController extends Controller
{
    public function index(Request $request, StudyProgramQuery $programs): View
    {
        abort_unless($programs->cityBelongsToProvince($request->integer('city_id') ?: null, $request->integer('province_id') ?: null), 422, 'شهر انتخاب‌شده متعلق به استان نیست.');

        return view('admin.study-programs.index', [
            'programs' => $programs->paginate($request->query()),
            'years' => ExamYear::query()->orderByDesc('year')->get(),
            'groups' => ExamGroup::query()->orderBy('id')->get(),
            'provinces' => Province::query()->orderBy('name')->get(),
            'cities' => City::query()->when($request->integer('province_id'), fn ($q, $id) => $q->where('province_id', $id))->orderBy('name')->get(),
            'courseTypes' => CourseType::query()->orderBy('name')->get(),
            'admissionTypes' => AdmissionType::query()->orderBy('name')->get(),
        ]);
    }

    public function show(StudyProgram $studyProgram): View
    {
        $studyProgram->load(['examYear', 'examGroup', 'province', 'city', 'institution', 'institutionCampus', 'academicField', 'courseType', 'admissionType']);

        return view('admin.study-programs.show', ['program' => $studyProgram]);
    }

    public function imports(): View
    {
        return view('admin.study-programs.imports', [
            'imports' => StudyProgramImport::query()->with(['examYear', 'examGroup'])->latest()->paginate(30),
        ]);
    }

    public function failures(StudyProgramImport $import): View
    {
        return view('admin.study-programs.failures', [
            'import' => $import,
            'failures' => $import->failures()->latest()->paginate(50),
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $provinceId = $request->integer('province_id');

        return response()->json(City::query()
            ->where('province_id', $provinceId)
            ->when($request->boolean('only_with_programs'), fn ($q) => $q->whereHas('programs'))
            ->orderBy('name')
            ->get(['id', 'name']));
    }

    public function reviews(): View
    {
        return view('admin.study-programs.reviews', [
            'reviews' => StudyProgramReviewRecord::query()->with(['examYear', 'examGroup'])->latest()->paginate(50),
        ]);
    }

    public function rejectReview(Request $request, StudyProgramReviewRecord $review): RedirectResponse
    {
        $review->update([
            'status' => 'rejected',
            'rejected_by' => $request->user()->id,
            'rejected_at' => now(),
            'review_note' => $request->string('review_note')->toString(),
        ]);

        return back()->with('success', 'ردیف بررسی رد شد.');
    }
}

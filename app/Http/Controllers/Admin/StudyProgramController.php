<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionType;
use App\Models\AcademicField;
use App\Models\City;
use App\Models\CourseType;
use App\Models\ExamGroup;
use App\Models\ExamYear;
use App\Models\Institution;
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
            'cities' => City::query()->when($request->integer('province_id'), fn ($q, $id) => $q->where('province_id', $id))->whereHas('programs', fn ($q) => $q->where('validation_status', 'validated'))->orderBy('normalized_name')->get(),
            'selectedInstitution' => Institution::query()->find($request->integer('institution_id')),
            'selectedAcademicField' => AcademicField::query()->find($request->integer('academic_field_id')),
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
            ->when($request->boolean('only_with_programs'), fn ($q) => $q->whereHas('programs', fn ($program) => $this->filterPrograms($program, $request)))
            ->orderBy('normalized_name')
            ->get(['id', 'name']));
    }

    public function institutions(Request $request): JsonResponse
    {
        $ids = StudyProgram::query()
            ->select('institution_id')
            ->whereNotNull('institution_id')
            ->where('validation_status', 'validated');
        $this->filterPrograms($ids, $request, false);

        $institutions = Institution::query()
            ->whereIn('id', $ids)
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('normalized_name')
            ->paginate(20, ['id', 'name'], 'page', max(1, $request->integer('page') ?: 1));

        return response()->json([
            'results' => $institutions->map(fn ($item) => ['id' => $item->id, 'text' => $item->name])->values(),
            'pagination' => ['more' => $institutions->hasMorePages()],
        ]);
    }

    public function academicFields(Request $request): JsonResponse
    {
        $ids = StudyProgram::query()
            ->select('academic_field_id')
            ->whereNotNull('academic_field_id')
            ->where('validation_status', 'validated');
        $this->filterPrograms($ids, $request);

        $fields = AcademicField::query()
            ->whereIn('id', $ids)
            ->when($request->string('search')->toString(), fn ($q, $search) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('normalized_name')
            ->paginate(20, ['id', 'name'], 'page', max(1, $request->integer('page') ?: 1));

        return response()->json([
            'results' => $fields->map(fn ($item) => ['id' => $item->id, 'text' => $item->name])->values(),
            'pagination' => ['more' => $fields->hasMorePages()],
        ]);
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

    private function filterPrograms($query, Request $request, bool $includeInstitution = true): void
    {
        $query->when($request->integer('exam_year_id'), fn ($q, $id) => $q->where('exam_year_id', $id))
            ->when($request->integer('exam_group_id'), fn ($q, $id) => $q->where('exam_group_id', $id))
            ->when($request->integer('province_id'), fn ($q, $id) => $q->where('province_id', $id))
            ->when($request->integer('city_id'), fn ($q, $id) => $q->where('city_id', $id))
            ->when($includeInstitution && $request->integer('institution_id'), fn ($q) => $q->where('institution_id', $request->integer('institution_id')));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\AcademicField;
use App\Models\AdmissionType;
use App\Models\City;
use App\Models\CourseType;
use App\Models\ExamGroup;
use App\Models\ExamYear;
use App\Models\FieldSelectionItem;
use App\Models\FieldSelectionPlan;
use App\Models\Institution;
use App\Models\Province;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\FieldSelectionService;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudyProgramAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionRoleSeeder::class);
    }

    #[Test]
    public function an_admin_can_create_edit_and_deactivate_a_manual_study_program_without_affecting_old_plan_items(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $data = $this->data();

        $this->actingAs($user)->post(route('admin.study-programs.store'), $data)->assertRedirect();
        $program = StudyProgram::query()->firstOrFail();
        $this->assertSame('manual', $program->source_type);
        $this->assertTrue($program->is_active);
        $this->assertSame('12345', $program->code);
        $this->assertCount(1, app(FieldSelectionService::class)->searchFieldCatalog(['q' => '12345']));

        $this->actingAs($user)->put(route('admin.study-programs.update', $program), [...$data, 'code' => '12346', 'description' => 'ویرایش شد'])->assertSessionHas('success');
        $this->assertSame('12346', $program->refresh()->code);
        $this->assertCount(1, app(FieldSelectionService::class)->searchFieldCatalog(['q' => '12346']));

        $item = $this->planItem($program, $user);
        $this->actingAs($user)->delete(route('admin.study-programs.destroy', $program))->assertRedirect(route('admin.study-programs.index'));
        $this->assertFalse($program->refresh()->is_active);
        $this->assertCount(0, app(FieldSelectionService::class)->searchFieldCatalog(['q' => '12346']));
        $this->assertDatabaseHas('field_selection_items', ['id' => $item->id, 'field_code' => '12346']);
    }

    #[Test]
    public function duplicate_manual_identity_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $data = $this->data();

        $this->actingAs($user)->post(route('admin.study-programs.store'), $data)->assertRedirect();
        $this->actingAs($user)->post(route('admin.study-programs.store'), $data)->assertSessionHasErrors('code');
        $this->assertSame(1, StudyProgram::query()->count());
    }

    private function data(): array
    {
        $year = ExamYear::query()->create(['year' => 1404, 'is_active' => true]);
        $group = ExamGroup::query()->create(['name' => 'تجربی', 'slug' => 'tajrobi']);
        $province = Province::query()->create(['name' => 'گیلان', 'normalized_name' => 'گیلان']);
        $city = City::query()->create(['province_id' => $province->id, 'name' => 'رشت', 'normalized_name' => 'رشت']);
        $institution = Institution::query()->create(['name' => 'دانشگاه گیلان', 'normalized_name' => 'دانشگاه گیلان', 'province_id' => $province->id, 'city_id' => $city->id]);
        $field = AcademicField::query()->create(['name' => 'پرستاری', 'normalized_name' => 'پرستاری']);
        $course = CourseType::query()->create(['name' => 'روزانه', 'slug' => 'day']);
        $admission = AdmissionType::query()->create(['name' => 'با آزمون', 'slug' => 'with_exam']);

        return [
            'exam_year_id' => $year->id,
            'exam_group_id' => $group->id,
            'code' => '12345',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'institution_id' => $institution->id,
            'academic_field_id' => $field->id,
            'course_type_id' => $course->id,
            'admission_type_id' => $admission->id,
            'first_semester_capacity' => 10,
            'second_semester_capacity' => 5,
            'is_active' => 1,
        ];
    }

    private function planItem(StudyProgram $program, User $user): FieldSelectionItem
    {
        $reservation = Reservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'slot_id' => ReservationSlot::factory()->create()->id,
            'status' => ReservationStatus::Confirmed,
        ]);
        $plan = FieldSelectionPlan::query()->create([
            'reservation_id' => $reservation->id,
            'student_id' => $reservation->student_id,
            'version' => 1,
            'status' => FieldSelectionPlan::STATUS_DRAFT,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return FieldSelectionItem::query()->create([
            'field_selection_plan_id' => $plan->id,
            'priority_order' => 1,
            'field_code' => $program->code,
            'field_name' => 'پرستاری',
            'city' => 'رشت',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\AcademicField;
use App\Models\AdmissionType;
use App\Models\Advisor;
use App\Models\City;
use App\Models\CourseType;
use App\Models\ExamGroup;
use App\Models\ExamYear;
use App\Models\FieldSelectionPlan;
use App\Models\Institution;
use App\Models\Province;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\FieldSelectionService;
use App\Services\PublicReservationLinkService;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionRoleSeeder::class);
    }

    #[Test]
    public function disabled_public_links_hide_reservation_details(): void
    {
        $reservation = $this->reservation();
        app(PublicReservationLinkService::class)->disable($reservation, User::factory()->create());

        $this->get(route('public.reservations.show', $reservation->public_token))
            ->assertOk()
            ->assertSee('لینک رزرو شما موقتاً غیرفعال شده است')
            ->assertDontSee($reservation->student->full_name);
    }

    #[Test]
    public function field_selection_page_shows_catalog_filters_from_database(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $reservation = $this->reservation();
        $plan = app(FieldSelectionService::class)->createPlanForReservation($reservation, $user);

        $province = Province::query()->create(['name' => 'گیلان', 'normalized_name' => 'گیلان']);
        $city = City::query()->create(['province_id' => $province->id, 'name' => 'رشت', 'normalized_name' => 'رشت']);
        $courseType = CourseType::query()->create(['name' => 'غیرانتفاعی', 'slug' => 'nonprofit']);
        $field = AcademicField::query()->create(['name' => 'پرستاری', 'normalized_name' => 'پرستاری']);
        $institution = Institution::query()->create(['name' => 'دانشگاه آزمایشی', 'normalized_name' => 'دانشگاه آزمایشی', 'province_id' => $province->id, 'city_id' => $city->id]);
        $this->studyProgram('33673', $province, $city, $institution, $field, $courseType);

        $this->actingAs($user)
            ->get(route('admin.reservations.field-selection.show', [$reservation, 'plan' => $plan]))
            ->assertOk()
            ->assertSee('جستجوی رشتهمحل از دیتابیس')
            ->assertSee('نوع دانشگاه / دوره')
            ->assertSee('گیلان')
            ->assertSee('غیرانتفاعی');
    }

    #[Test]
    public function field_selection_catalog_search_filters_by_field_province_city_and_course_type(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $gilan = Province::query()->create(['name' => 'گیلان', 'normalized_name' => 'گیلان']);
        $tehran = Province::query()->create(['name' => 'تهران', 'normalized_name' => 'تهران']);
        $rasht = City::query()->create(['province_id' => $gilan->id, 'name' => 'رشت', 'normalized_name' => 'رشت']);
        $lahijan = City::query()->create(['province_id' => $gilan->id, 'name' => 'لاهیجان', 'normalized_name' => 'لاهیجان']);
        $tehranCity = City::query()->create(['province_id' => $tehran->id, 'name' => 'تهران', 'normalized_name' => 'تهران']);
        $day = CourseType::query()->create(['name' => 'روزانه', 'slug' => 'day']);
        $nonprofit = CourseType::query()->create(['name' => 'غیرانتفاعی', 'slug' => 'nonprofit']);
        $nursing = AcademicField::query()->create(['name' => 'پرستاری', 'normalized_name' => 'پرستاری']);
        $engineering = AcademicField::query()->create(['name' => 'مهندسی کامپیوتر', 'normalized_name' => 'مهندسی کامپیوتر']);
        $institution = Institution::query()->create(['name' => 'دانشگاه آزمایشی', 'normalized_name' => 'دانشگاه آزمایشی', 'province_id' => $gilan->id, 'city_id' => $rasht->id]);

        $this->studyProgram('33673', $gilan, $rasht, $institution, $nursing, $day);
        $this->studyProgram('33674', $gilan, $lahijan, $institution, $nursing, $day);
        $this->studyProgram('33675', $tehran, $tehranCity, $institution, $nursing, $day);
        $this->studyProgram('41200', $gilan, $rasht, $institution, $engineering, $nonprofit);

        $this->actingAs($user)
            ->getJson(route('admin.field-selection.search-fields', [
                'q' => 'پرستاری',
                'province_id' => $gilan->id,
                'city_id' => $rasht->id,
                'course_type_id' => $day->id,
            ]))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.field_code', '33673')
            ->assertJsonPath('items.0.field_name', 'پرستاری')
            ->assertJsonPath('items.0.city', 'رشت')
            ->assertJsonPath('items.0.course_type', 'روزانه');

        $this->actingAs($user)
            ->getJson(route('admin.field-selection.search-fields', [
                'q' => 'پرستاری',
                'province_id' => $gilan->id,
                'city_id' => $rasht->id,
                'course_type_id' => $nonprofit->id,
            ]))
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('items', []);

        $this->actingAs($user)
            ->getJson(route('admin.field-selection.search-fields', [
                'province_id' => $gilan->id,
                'city_id' => $rasht->id,
                'course_type_id' => $nonprofit->id,
            ]))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.field_code', '41200')
            ->assertJsonPath('items.0.field_name', 'مهندسی کامپیوتر');
    }

    #[Test]
    public function field_selection_city_options_follow_selected_province_and_field_query(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $gilan = Province::query()->create(['name' => 'گیلان', 'normalized_name' => 'گیلان']);
        $rasht = City::query()->create(['province_id' => $gilan->id, 'name' => 'رشت', 'normalized_name' => 'رشت']);
        $lahijan = City::query()->create(['province_id' => $gilan->id, 'name' => 'لاهیجان', 'normalized_name' => 'لاهیجان']);
        $day = CourseType::query()->create(['name' => 'روزانه', 'slug' => 'day']);
        $nursing = AcademicField::query()->create(['name' => 'پرستاری', 'normalized_name' => 'پرستاری']);
        $engineering = AcademicField::query()->create(['name' => 'مهندسی کامپیوتر', 'normalized_name' => 'مهندسی کامپیوتر']);
        $institution = Institution::query()->create(['name' => 'دانشگاه آزمایشی', 'normalized_name' => 'دانشگاه آزمایشی', 'province_id' => $gilan->id, 'city_id' => $rasht->id]);

        $this->studyProgram('33673', $gilan, $rasht, $institution, $nursing, $day);
        $this->studyProgram('41200', $gilan, $lahijan, $institution, $engineering, $day);

        $this->actingAs($user)
            ->getJson(route('admin.field-selection.filter-options.cities', [
                'province_id' => $gilan->id,
                'q' => 'پرستاری',
                'course_type_id' => $day->id,
            ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'رشت');
    }

    #[Test]
    public function adding_a_catalog_field_persists_description_university_and_booklet_source(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $reservation = $this->reservation();
        $plan = app(FieldSelectionService::class)->createPlanForReservation($reservation, $user);

        $province = Province::query()->create(['name' => 'تهران', 'normalized_name' => 'تهران']);
        $city = City::query()->create(['province_id' => $province->id, 'name' => 'تهران', 'normalized_name' => 'تهران']);
        $courseType = CourseType::query()->create(['name' => 'روزانه', 'slug' => 'day']);
        $field = AcademicField::query()->create(['name' => 'مهندسی برق', 'normalized_name' => 'مهندسی برق']);
        $institution = Institution::query()->create(['name' => 'دانشگاه تهران', 'normalized_name' => 'دانشگاه تهران', 'province_id' => $province->id, 'city_id' => $city->id]);
        $program = $this->studyProgram('12345', $province, $city, $institution, $field, $courseType, 'توضیحات رشته برق', 'riazi', 'ریاضی');

        $this->actingAs($user)
            ->getJson(route('admin.field-selection.search-fields', ['q' => 'برق']))
            ->assertOk()
            ->assertJsonPath('items.0.field_code', '12345')
            ->assertJsonPath('items.0.field_description', 'توضیحات رشته برق')
            ->assertJsonPath('items.0.university_name', 'دانشگاه تهران')
            ->assertJsonPath('items.0.booklet_source', 'دفترچه ریاضی');

        $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->withHeader('X-CSRF-TOKEN', 'test-token')
            ->postJson(route('admin.field-selection-plans.items.from-catalog', $plan), ['field_catalog_id' => $program->id])
            ->assertOk()
            ->assertJsonPath('item.field_description', 'توضیحات رشته برق')
            ->assertJsonPath('item.university_name', 'دانشگاه تهران');

        $this->assertDatabaseHas('field_selection_items', [
            'field_selection_plan_id' => $plan->id,
            'field_code' => '12345',
            'field_description' => 'توضیحات رشته برق',
            'university_name' => 'دانشگاه تهران',
            'university_type' => 'روزانه',
        ]);
    }

    #[Test]
    public function management_print_and_public_pages_show_field_description_and_university_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $reservation = $this->reservation(['public_token_expires_at' => now()->subDay()]);
        $service = app(FieldSelectionService::class);
        $plan = $service->createPlanForReservation($reservation, $user);
        $service->addItem($plan, [
            'field_code' => '55555',
            'field_name' => 'علوم کامپیوتر',
            'field_description' => 'توضیحات رشته علوم کامپیوتر',
            'city' => 'تهران',
            'university_name' => 'دانشگاه صنعتی شریف',
            'university_type' => 'روزانه',
        ], $user);
        $service->publish($plan, $user);
        $service->showToStudent($plan->refresh(), $user);

        $this->assertTrue($reservation->refresh()->public_token_expires_at->isFuture());

        $this->actingAs($user)
            ->get(route('admin.reservations.field-selection.show', [$reservation, 'plan' => $plan]))
            ->assertOk()
            ->assertSee('توضیحات رشته')
            ->assertSee('دانشگاه صنعتی شریف')
            ->assertSee('توضیحات رشته علوم کامپیوتر');

        $this->actingAs($user)
            ->get(route('admin.field-selection-plans.print', $plan))
            ->assertOk()
            ->assertSee('دانشگاه صنعتی شریف')
            ->assertSee('توضیحات رشته علوم کامپیوتر');

        $this->get(route('public.reservations.show', $reservation->public_token))
            ->assertOk()
            ->assertSee('مشاهده انتخاب رشته');

        $this->get(route('public.reservations.field-selection.show', $reservation->public_token))
            ->assertOk()
            ->assertSee('دانشگاه صنعتی شریف')
            ->assertSee('توضیحات رشته علوم کامپیوتر');
    }

    #[Test]
    public function public_field_selection_uses_configured_visible_versions_instead_of_latest_only(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $reservation = $this->reservation();
        $service = app(FieldSelectionService::class);

        $first = $service->createPlanForReservation($reservation, $user);
        $service->addItem($first, ['field_code' => '11111', 'field_name' => 'پرستاری', 'city' => 'رشت'], $user);
        $service->publish($first, $user);
        $service->showToStudent($first->refresh(), $user);

        $second = $service->createNewVersionFromExisting($first->refresh(), $user);
        $service->addItem($second, ['field_code' => '22222', 'field_name' => 'مامایی', 'city' => 'تهران'], $user);
        $service->publish($second, $user);
        $service->setPublicVisibility($second->refresh(), false, $user);

        $this->get(route('public.reservations.field-selection.show', $reservation->public_token))
            ->assertOk()
            ->assertSee('11111')
            ->assertDontSee('22222');

        $this->get(route('public.reservations.field-selection.show', [$reservation->public_token, 'plan' => $second->id]))
            ->assertNotFound();
    }

    #[Test]
    public function numeric_catalog_search_prioritizes_exact_code_and_accepts_persian_digits(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $province = Province::query()->create(['name' => 'تهران', 'normalized_name' => 'تهران']);
        $city = City::query()->create(['province_id' => $province->id, 'name' => 'تهران', 'normalized_name' => 'تهران']);
        $courseType = CourseType::query()->create(['name' => 'روزانه', 'slug' => 'day']);
        $nursing = AcademicField::query()->create(['name' => 'پرستاری', 'normalized_name' => 'پرستاری']);
        $veterinary = AcademicField::query()->create(['name' => 'دامپزشکی', 'normalized_name' => 'دامپزشکی']);
        $institution = Institution::query()->create(['name' => 'دانشگاه آزمایشی', 'normalized_name' => 'دانشگاه آزمایشی', 'province_id' => $province->id, 'city_id' => $city->id]);

        $this->studyProgram('33673', $province, $city, $institution, $nursing, $courseType);
        $this->studyProgram('336730', $province, $city, $institution, $veterinary, $courseType);

        $this->actingAs($user)
            ->getJson(route('admin.field-selection.search-fields', ['q' => '۳۳۶۷۳']))
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('items.0.field_code', '33673')
            ->assertJsonPath('items.1.field_code', '336730');
    }

    #[Test]
    public function catalog_search_prefers_academic_field_matches_and_drops_unrelated_description_noise(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $province = Province::query()->create(['name' => 'گیلان', 'normalized_name' => 'گیلان']);
        $city = City::query()->create(['province_id' => $province->id, 'name' => 'رشت', 'normalized_name' => 'رشت']);
        $courseType = CourseType::query()->create(['name' => 'روزانه', 'slug' => 'day']);
        $nursing = AcademicField::query()->create(['name' => 'پرستاری', 'normalized_name' => 'پرستاری']);
        $veterinary = AcademicField::query()->create(['name' => 'دامپزشکی', 'normalized_name' => 'دامپزشکی']);
        $institution = Institution::query()->create(['name' => 'دانشگاه آزمایشی', 'normalized_name' => 'دانشگاه آزمایشی', 'province_id' => $province->id, 'city_id' => $city->id]);

        $this->studyProgram('33673', $province, $city, $institution, $nursing, $courseType);
        $this->studyProgram('44990', $province, $city, $institution, $veterinary, $courseType, 'توضیح متفرقه شامل واژه پرستاری');

        $this->actingAs($user)
            ->getJson(route('admin.field-selection.search-fields', ['q' => 'پرستاری']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.field_code', '33673')
            ->assertJsonPath('items.0.field_name', 'پرستاری');
    }

    #[Test]
    public function field_selection_catalog_search_returns_every_matching_program(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $province = Province::query()->create(['name' => 'گیلان', 'normalized_name' => 'گیلان']);
        $city = City::query()->create(['province_id' => $province->id, 'name' => 'رشت', 'normalized_name' => 'رشت']);
        $courseType = CourseType::query()->create(['name' => 'روزانه', 'slug' => 'day']);
        $nursing = AcademicField::query()->create(['name' => 'پرستاری', 'normalized_name' => 'پرستاری']);
        $institution = Institution::query()->create(['name' => 'دانشگاه آزمایشی', 'normalized_name' => 'دانشگاه آزمایشی', 'province_id' => $province->id, 'city_id' => $city->id]);

        foreach (range(1, 31) as $number) {
            $this->studyProgram((string) (50000 + $number), $province, $city, $institution, $nursing, $courseType);
        }

        $this->actingAs($user)
            ->getJson(route('admin.field-selection.search-fields', ['q' => 'پرستاری']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('count', 31)
            ->assertJsonCount(31, 'items');
    }

    #[Test]
    public function field_selection_responsible_print_shows_student_name_without_contact_information(): void
    {
        $student = Student::factory()->create([
            'full_name' => 'دانش‌آموز محرمانه',
            'major' => 'تجربی',
            'score' => '12345',
        ]);
        $consultant = User::factory()->create(['name' => 'مسئول انتخاب رشته']);
        $consultant->assignRole(User::ROLE_CONSULTANT);
        $advisor = Advisor::factory()->create(['user_id' => $consultant->id]);
        $slot = ReservationSlot::factory()->create(['advisor_id' => $advisor->id]);
        $reservation = Reservation::query()->create([
            'student_id' => $student->id,
            'slot_id' => $slot->id,
            'advisor_id' => $advisor->id,
            'status' => ReservationStatus::Confirmed,
            'public_token' => str()->random(80),
            'public_token_expires_at' => now()->addDay(),
        ]);

        $service = app(FieldSelectionService::class);
        $plan = $service->createPlanForReservation($reservation, $consultant);
        $service->addItem($plan, ['field_code' => '55555', 'field_name' => 'پرستاری', 'city' => 'رشت'], $consultant);
        $service->publish($plan, $consultant);

        $this->actingAs($consultant)
            ->get(route('admin.field-selection-plans.print', $plan))
            ->assertOk()
            ->assertSee('55555')
            ->assertSee('دانش‌آموز محرمانه');
    }

    #[Test]
    public function publishing_then_editing_keeps_the_old_version_and_copies_its_items(): void
    {
        $reservation = $this->reservation();
        $user = User::factory()->create();
        $service = app(FieldSelectionService::class);
        $first = $service->createPlanForReservation($reservation, $user);
        $firstItem = $service->addItem($first, ['field_code' => '101', 'field_name' => 'رشته اول', 'city' => 'تهران'], $user);
        $secondItem = $service->addItem($first, ['field_code' => '102', 'field_name' => 'رشته دوم', 'city' => 'شیراز'], $user);

        $service->reorderItems($first, [$secondItem->id, $firstItem->id], $user);
        $service->publish($first, $user);
        $second = $service->createNewVersionFromExisting($first->refresh(), $user);

        $this->assertSame('published', $first->refresh()->status);
        $this->assertSame('draft', $second->status);
        $this->assertSame(2, $second->version);
        $this->assertSame(['102', '101'], $second->items()->pluck('field_code')->all());
    }

    #[Test]
    public function publishing_and_archiving_a_plan_do_not_change_other_versions(): void
    {
        $reservation = $this->reservation();
        $user = User::factory()->create();
        $service = app(FieldSelectionService::class);
        $first = $service->createPlanForReservation($reservation, $user);
        $service->addItem($first, ['field_code' => '101', 'field_name' => 'رشته اول', 'city' => 'تهران'], $user);
        $service->publish($first, $user, true);
        $second = $service->createPlanForReservation($reservation, $user);
        $service->addItem($second, ['field_code' => '102', 'field_name' => 'رشته دوم', 'city' => 'شیراز'], $user);
        $service->publish($second, $user, true);

        $this->assertSame(FieldSelectionPlan::STATUS_PUBLISHED, $first->refresh()->status);
        $this->assertTrue($first->refresh()->is_public_visible);
        $this->assertSame(FieldSelectionPlan::STATUS_PUBLISHED, $second->refresh()->status);
        $this->assertTrue($second->refresh()->is_public_visible);

        $service->archive($second->refresh(), $user);

        $this->assertSame(FieldSelectionPlan::STATUS_PUBLISHED, $first->refresh()->status);
        $this->assertTrue($first->refresh()->is_public_visible);
        $this->assertSame(FieldSelectionPlan::STATUS_ARCHIVED, $second->refresh()->status);
        $this->assertFalse($second->refresh()->is_public_visible);
    }

    #[Test]
    public function a_reservation_keeps_one_editable_plan_per_exam_type(): void
    {
        $reservation = $this->reservation();
        $reservation->student->update(['exam_type' => ['ریاضی', 'تجربی']]);
        $service = app(FieldSelectionService::class);
        $user = User::factory()->create();

        $riazi = $service->getOrCreatePlanForExamType($reservation->fresh(), 'riazi', $user);
        $tajrobi = $service->getOrCreatePlanForExamType($reservation->fresh(), 'tajrobi', $user);

        $this->assertSame($riazi->id, $service->getOrCreatePlanForExamType($reservation->fresh(), 'riazi', $user)->id);
        $this->assertNotSame($riazi->id, $tajrobi->id);
        $this->assertSame('riazi', $riazi->exam_type_key);
        $this->assertSame('tajrobi', $tajrobi->exam_type_key);
    }

    private function studyProgram(string $code, Province $province, City $city, Institution $institution, AcademicField $field, CourseType $courseType, ?string $description = null, string $groupSlug = 'tajrobi', string $groupName = 'تجربی'): StudyProgram
    {
        $year = ExamYear::query()->firstOrCreate(['year' => 1404], ['is_active' => true]);
        $group = ExamGroup::query()->firstOrCreate(['slug' => $groupSlug], ['name' => $groupName]);
        $admission = AdmissionType::query()->firstOrCreate(['slug' => 'with_exam'], ['name' => 'با آزمون']);

        return StudyProgram::query()->create([
            'exam_year_id' => $year->id,
            'exam_group_id' => $group->id,
            'code' => $code,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'institution_id' => $institution->id,
            'academic_field_id' => $field->id,
            'course_type_id' => $courseType->id,
            'admission_type_id' => $admission->id,
            'description' => $description,
            'first_semester_capacity' => 10,
            'second_semester_capacity' => 5,
            'source_file' => 'test.pdf',
            'source_row' => (int) $code,
            'source_hash' => hash('sha256', 'source|'.$code),
            'identity_hash' => hash('sha256', implode('|', [$year->id, $group->id, $code, $institution->id, $field->id, $courseType->id])),
            'validation_status' => 'validated',
        ]);
    }

    private function reservation(array $overrides = []): Reservation
    {
        $student = Student::factory()->create(['full_name' => 'دانش‌آموز آزمایشی']);

        return Reservation::query()->create([
            ...[
                'student_id' => $student->id,
                'slot_id' => ReservationSlot::factory()->create()->id,
                'status' => ReservationStatus::Confirmed,
                'public_token' => str()->random(80),
                'public_token_expires_at' => now()->addDay(),
            ],
            ...$overrides,
        ])->load('student');
    }
}

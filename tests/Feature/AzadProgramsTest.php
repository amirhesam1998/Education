<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Advisor;
use App\Models\AzadProgram;
use App\Models\AzadSelectionItem;
use App\Models\AzadSelectionPlan;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Models\Student;
use App\Models\User;
use App\Services\Azad\AzadProgramImporter;
use App\Services\Azad\AzadSelectionService;
use App\Services\FieldSelectionService;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AzadProgramsTest extends TestCase
{
    use RefreshDatabase;

    private string $storage;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionRoleSeeder::class);

        // The import writes a backup under storage/app; keep it out of the real storage folder.
        $this->storage = sys_get_temp_dir().'/azad-import-test-'.getmypid();
        File::ensureDirectoryExists($this->storage.'/app');
        $this->app->useStoragePath($this->storage);
        $this->root = $this->storage.'/azad';
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    #[Test]
    public function the_committed_azad_booklets_import_completely_and_a_second_run_changes_nothing(): void
    {
        $this->artisan('education:import-azad-programs')->assertSuccessful();

        $manifest = app(AzadProgramImporter::class)->manifest(database_path('seeders/data/azad/1405'));
        $this->assertSame($manifest['programs'], AzadProgram::query()->count());
        foreach ($manifest['booklets'] as $booklet) {
            $this->assertSame($booklet['programs'], AzadProgram::query()->where('booklet', $booklet['key'])->count(), $booklet['key']);
        }
        $this->assertSame($manifest['counts']['units'], AzadProgram::query()->distinct()->count('unit_code'));
        $this->assertSame($manifest['counts']['provinces'], AzadProgram::query()->distinct()->count('province'));

        // «واحد سراب ۱۲۴» on the sample page: the code beside the red heading is the unit code.
        $sarab = AzadProgram::query()->where('booklet', 'sarasari')->where('unit_code', '124')->where('field_code', '10401')->firstOrFail();
        $this->assertSame(['واحد سراب', 'سراب', 'آذربایجان شرقی', 'پرستاری', 'tajrobi'], [$sarab->unit_name, $sarab->city, $sarab->province, $sarab->field_name, $sarab->exam_group]);

        // One name and one city per unit, every row with a place.
        $this->assertSame(0, AzadProgram::query()->where('province', '')->orWhere('city', '')->count());
        $this->assertSame(
            AzadProgram::query()->distinct()->count('unit_code'),
            AzadProgram::query()->select('unit_code', 'unit_name', 'city')->distinct()->get()->count(),
        );

        $this->artisan('education:import-azad-programs')->expectsOutputToContain('قبلاً وارد شده')->assertSuccessful();
        $this->artisan('education:import-azad-programs', ['--again' => true])->assertSuccessful();
        $this->assertSame($manifest['programs'], AzadProgram::query()->count());
        $this->assertSame($manifest['programs'], (int) \App\Models\AzadProgramImport::query()->latest('id')->value('unchanged_rows'));
    }

    #[Test]
    public function a_new_file_updates_changed_rows_keeps_admin_edits_and_drops_removed_ones(): void
    {
        $this->writeAzad([
            $this->program('124', '10401', ['capacity_first' => 15]),
            $this->program('124', '10402', ['field_name' => 'مامایی']),
            $this->program('125', '10401', ['unit_name' => 'واحد مرند', 'city' => 'مرند']),
        ]);
        $this->artisan('education:import-azad-programs', ['--path' => $this->root])->assertSuccessful();
        $this->assertSame(3, AzadProgram::query()->count());

        $admin = $this->admin();
        $edited = AzadProgram::query()->where('unit_code', '125')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.azad-programs.update', $edited), [
            ...$edited->only(['year', 'booklet', 'province', 'city', 'unit_code', 'unit_name', 'field_code', 'gender', 'exam_group']),
            'field_name' => 'پرستاری (اصلاح شده)',
            'capacity_first' => 9,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->writeAzad([
            $this->program('124', '10401', ['capacity_first' => 20]),
            $this->program('125', '10401', ['unit_name' => 'واحد مرند', 'city' => 'مرند', 'capacity_first' => 30]),
        ]);
        $this->artisan('education:import-azad-programs', ['--path' => $this->root])->assertSuccessful();

        $this->assertSame(20, AzadProgram::query()->where('unit_code', '124')->where('field_code', '10401')->value('capacity_first'));
        $this->assertFalse(AzadProgram::query()->where('field_code', '10402')->exists());
        $this->assertSame(['پرستاری (اصلاح شده)', 9], [$edited->fresh()->field_name, $edited->fresh()->capacity_first]);
    }

    #[Test]
    public function a_programmes_file_that_does_not_match_its_manifest_is_refused(): void
    {
        $dir = $this->writeAzad([$this->program('124', '10401')]);
        file_put_contents($dir.'/programs.jsonl.gz', gzencode(json_encode($this->program('124', '10402'), JSON_UNESCAPED_UNICODE)."\n"));

        $this->expectException(\RuntimeException::class);

        try {
            $this->artisan('education:import-azad-programs', ['--path' => $this->root])->run();
        } finally {
            $this->assertSame(0, AzadProgram::query()->count());
        }
    }

    #[Test]
    public function the_catalogue_filters_by_province_city_unit_gender_and_booklet(): void
    {
        $this->seedPrograms();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.azad-programs.index', ['province' => 'آذربایجان شرقی']))
            ->assertOk()
            ->assertSee('واحد سراب')
            ->assertSee('واحد مرند')
            ->assertDontSee('واحد رشت');

        $this->actingAs($admin)->get(route('admin.azad-programs.index', ['province' => 'آذربایجان شرقی', 'city' => 'مرند']))
            ->assertOk()
            ->assertSee('واحد مرند')
            ->assertDontSee('واحد سراب');

        $this->actingAs($admin)->get(route('admin.azad-programs.index', ['booklet' => 'kardani_peyvaste']))
            ->assertOk()
            ->assertSee('حسابداری')
            ->assertDontSee('مامایی');

        $this->actingAs($admin)->getJson(route('admin.azad-programs.cities', ['province' => 'آذربایجان شرقی']))
            ->assertExactJson(['سراب', 'مرند']);
        $this->actingAs($admin)->getJson(route('admin.azad-programs.units', ['province' => 'آذربایجان شرقی', 'city' => 'سراب']))
            ->assertExactJson([['code' => '124', 'name' => 'واحد سراب']]);

        // «زن»: programmes that admit women, mixed ones included; «فقط زن و مرد»: mixed only.
        $this->actingAs($admin)->getJson(route('admin.azad-selection.search', ['gender' => 'زن']))
            ->assertJsonPath('total', 4);
        $this->actingAs($admin)->getJson(route('admin.azad-selection.search', ['gender' => 'only_mixed']))
            ->assertJsonPath('total', 3);
        $this->actingAs($admin)->getJson(route('admin.azad-selection.search', ['gender' => 'مرد']))
            ->assertJsonPath('total', 4);
        $this->actingAs($admin)->getJson(route('admin.azad-selection.search', ['q' => 'مامایی', 'province' => 'آذربایجان شرقی']))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.unit_name', 'واحد سراب');
        $this->actingAs($admin)->getJson(route('admin.azad-selection.search'))
            ->assertJsonPath('total', 0);
    }

    #[Test]
    public function a_programme_is_added_by_unit_code_and_field_code_with_every_detail_from_the_booklet(): void
    {
        $this->seedPrograms();
        $admin = $this->admin();
        $reservation = $this->reservation();

        $this->actingAs($admin)->get(route('admin.reservations.azad-selection.show', $reservation))
            ->assertOk()
            ->assertSee('ثبت با کد محل دانشگاهی و کد رشته تحصیلی')
            ->assertSee('آذربایجان شرقی');
        $plan = AzadSelectionPlan::query()->where('reservation_id', $reservation->id)->firstOrFail();

        // Persian digits are accepted.
        $found = $this->actingAs($admin)->getJson(route('admin.azad-selection.lookup', ['unit_code' => '۱۲۴', 'field_code' => '۱۰۴۰۱']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.unit_name', 'واحد سراب')
            ->json('items.0');

        $this->actingAs($admin)->postJson(route('admin.azad-selection-plans.items.store', $plan), ['azad_program_id' => $found['id'], 'note' => 'اولویت اول'])
            ->assertOk()
            ->assertJsonPath('count', 1);

        $item = AzadSelectionItem::query()->firstOrFail();
        $this->assertSame(
            ['124', 'واحد سراب', '10401', 'پرستاری', 'سراب', 'آذربایجان شرقی', 'زن و مرد', 15, 1, 'اولویت اول'],
            [$item->unit_code, $item->unit_name, $item->field_code, $item->field_name, $item->city, $item->province, $item->gender, $item->capacity_first, $item->priority_order, $item->note],
        );

        $this->actingAs($admin)->postJson(route('admin.azad-selection-plans.items.store', $plan), ['azad_program_id' => $found['id']])
            ->assertUnprocessable();

        // The student's copy stays when the catalogue row is deleted.
        AzadProgram::query()->whereKey($found['id'])->delete();
        $this->assertSame('واحد سراب', $item->fresh()->unit_name);
    }

    #[Test]
    public function reordering_and_deleting_keep_priorities_consecutive(): void
    {
        $this->seedPrograms();
        $admin = $this->admin();
        $service = app(AzadSelectionService::class);
        $plan = $service->getOrCreatePlan($this->reservation(), $admin);
        $items = AzadProgram::query()->orderBy('id')->take(3)->get()->map(fn ($p) => $service->addProgram($plan, $p, $admin));

        $this->actingAs($admin)->postJson(route('admin.azad-selection-plans.reorder', $plan), ['ordered_item_ids' => [$items[2]->id, $items[0]->id, $items[1]->id]])
            ->assertOk();
        $this->assertSame([$items[2]->id, $items[0]->id, $items[1]->id], $plan->items()->pluck('id')->all());

        $this->actingAs($admin)->deleteJson(route('admin.azad-selection-items.destroy', $items[0]))->assertOk();
        $this->assertSame([1, 2], $plan->items()->pluck('priority_order')->all());

        $this->actingAs($admin)->postJson(route('admin.azad-selection-plans.reorder', $plan), ['ordered_item_ids' => [$items[1]->id]])
            ->assertUnprocessable();
    }

    #[Test]
    public function the_reservation_page_has_both_selection_buttons_and_the_sidebar_both_catalogues(): void
    {
        $admin = $this->admin();
        $reservation = $this->reservation();

        $this->actingAs($admin)->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('مدیریت انتخاب رشته</a>', false)
            ->assertSee('مدیریت انتخاب رشته آزاد')
            ->assertSee('رشته‌محل دولتی')
            ->assertSee('رشته‌محل آزاد');
    }

    #[Test]
    public function azad_permissions_are_seeded_and_enforced(): void
    {
        foreach (['view_azad_programs', 'create_azad_programs', 'update_azad_programs', 'delete_azad_programs', 'view_azad_field_selection', 'manage_azad_field_selection'] as $permission) {
            $this->assertTrue(Role::findByName('Super Admin')->hasPermissionTo($permission), $permission);
            $this->assertTrue(Role::findByName('Admin / Branch Manager')->hasPermissionTo($permission), $permission);
        }
        $this->assertTrue(Role::findByName('Consultant')->hasPermissionTo('manage_azad_field_selection'));
        $this->assertTrue(Role::findByName('Operator')->hasPermissionTo('view_azad_field_selection'));
        $this->assertFalse(Role::findByName('Operator')->hasPermissionTo('manage_azad_field_selection'));
        $this->assertFalse(Role::findByName('Accountant')->hasPermissionTo('view_azad_field_selection'));
        $this->assertSame('مدیریت انتخاب رشته آزاد', \App\Support\PermissionLabels::permission('manage_azad_field_selection'));

        $this->seedPrograms();
        $reservation = $this->reservation();
        $plan = app(AzadSelectionService::class)->getOrCreatePlan($reservation, $this->admin());
        $program = AzadProgram::query()->firstOrFail();

        $operator = User::factory()->create();
        $operator->assignRole('Operator');
        $this->actingAs($operator)->get(route('admin.azad-programs.index'))->assertForbidden();
        $this->actingAs($operator)->postJson(route('admin.azad-selection-plans.items.store', $plan), ['azad_program_id' => $program->id])->assertForbidden();

        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        $this->actingAs($accountant)->get(route('admin.reservations.azad-selection.show', $reservation))->assertForbidden();

        // A consultant works only on their own students.
        $consultant = User::factory()->create();
        $consultant->assignRole(User::ROLE_CONSULTANT);
        $this->actingAs($consultant)->postJson(route('admin.azad-selection-plans.items.store', $plan), ['azad_program_id' => $program->id])->assertForbidden();

        $advisor = Advisor::factory()->create(['user_id' => $consultant->id]);
        $own = $this->reservation(['advisor_id' => $advisor->id, 'slot_id' => ReservationSlot::factory()->create(['advisor_id' => $advisor->id])->id]);
        $this->actingAs($consultant)->get(route('admin.reservations.azad-selection.show', $own))->assertOk();
        $ownPlan = AzadSelectionPlan::query()->where('reservation_id', $own->id)->firstOrFail();
        $this->actingAs($consultant)->postJson(route('admin.azad-selection-plans.items.store', $ownPlan), ['azad_program_id' => $program->id])->assertOk();
    }

    #[Test]
    public function the_student_sees_and_prints_state_and_azad_lists_once_each_is_published_and_visible(): void
    {
        $this->seedPrograms();
        $admin = $this->admin();
        $reservation = $this->reservation();
        $azad = app(AzadSelectionService::class);
        $plan = $azad->getOrCreatePlan($reservation, $admin);
        $azad->addProgram($plan, AzadProgram::query()->where('unit_code', '124')->where('field_code', '10401')->firstOrFail(), $admin);

        $this->get(route('public.reservations.show', $reservation->public_token))
            ->assertOk()
            ->assertDontSee('مشاهده انتخاب رشته آزاد');
        $this->get(route('public.reservations.azad-selection.print', [$reservation->public_token, $plan]))->assertNotFound();

        $azad->publish($plan, $admin, true);

        $this->get(route('public.reservations.show', $reservation->public_token))
            ->assertOk()
            ->assertSee('مشاهده انتخاب رشته آزاد')
            ->assertSee(route('public.reservations.azad-selection.print', [$reservation->public_token, $plan]), false);
        $this->get(route('public.reservations.azad-selection.print', [$reservation->public_token, $plan]))
            ->assertOk()
            ->assertSee('دانشگاه آزاد اسلامی')
            ->assertSee('واحد سراب')
            ->assertSee('10401')
            ->assertSee('window.print()', false);
        $this->get(route('public.reservations.azad-selection.show', [$reservation->public_token, $plan]))
            ->assertOk()
            ->assertSee('واحد سراب');

        // With a state list as well, both are offered.
        $state = app(FieldSelectionService::class);
        $statePlan = $state->createPlanForReservation($reservation, $admin);
        $state->addItem($statePlan, ['field_code' => '55555', 'field_name' => 'پزشکی', 'city' => 'تبریز'], $admin);
        $state->publish($statePlan, $admin, true);

        $this->get(route('public.reservations.show', $reservation->public_token))
            ->assertOk()
            ->assertSee(route('public.reservations.field-selection.plan.print', [$reservation->public_token, $statePlan]), false)
            ->assertSee(route('public.reservations.azad-selection.print', [$reservation->public_token, $plan]), false);

        // Another reservation's link cannot open this list.
        $other = $this->reservation();
        $this->get(route('public.reservations.azad-selection.print', [$other->public_token, $plan]))->assertNotFound();

        $azad->setVisibility($plan, false, $admin);
        $this->get(route('public.reservations.azad-selection.print', [$reservation->public_token, $plan]))->assertNotFound();
    }

    #[Test]
    public function the_admin_print_shows_the_azad_list(): void
    {
        $this->seedPrograms();
        $admin = $this->admin();
        $azad = app(AzadSelectionService::class);
        $plan = $azad->getOrCreatePlan($this->reservation(), $admin);
        $azad->addProgram($plan, AzadProgram::query()->where('booklet', 'kardani_peyvaste')->firstOrFail(), $admin);

        $this->actingAs($admin)->get(route('admin.azad-selection-plans.print', $plan))
            ->assertOk()
            ->assertSee('دانش‌آموز آزمایشی')
            ->assertSee('حسابداری')
            ->assertSee('کاردانی پیوسته - سوابق تحصیلی');
    }

    #[Test]
    public function the_deploy_imports_the_azad_programmes_after_the_permissions_are_seeded(): void
    {
        $deploy = (string) file_get_contents(base_path('.github/workflows/deploy.yml'));
        $seed = strpos($deploy, 'ProductionSeeder');
        $import = strpos($deploy, 'php artisan education:import-azad-programs --no-interaction');

        $this->assertNotFalse($seed);
        $this->assertNotFalse($import);
        $this->assertGreaterThan($seed, $import);
        $this->assertGreaterThan(strpos($deploy, 'php artisan migrate --force'), $import);
    }

    private function seedPrograms(): void
    {
        $this->writeAzad([
            $this->program('124', '10401', ['capacity_first' => 15]),
            $this->program('124', '10402', ['field_name' => 'مامایی', 'gender' => 'زن']),
            $this->program('125', '10401', ['unit_name' => 'واحد مرند', 'city' => 'مرند']),
            $this->program('130', '10401', ['unit_name' => 'واحد رشت', 'city' => 'رشت', 'province' => 'گیلان', 'gender' => 'مرد']),
            $this->program('124', '3101', [
                'booklet' => 'kardani_peyvaste', 'admission' => 'records', 'level' => 'کاردانی پیوسته', 'field_name' => 'حسابداری',
                'exam_group' => null, 'capacity_first' => null, 'capacity_second' => null,
            ]),
        ]);
        $this->artisan('education:import-azad-programs', ['--path' => $this->root])->assertSuccessful();
    }

    /** @param list<array<string, mixed>> $programs */
    private function writeAzad(array $programs): string
    {
        $dir = $this->root.'/1405';
        File::ensureDirectoryExists($dir);

        $lines = array_map(fn (array $p) => json_encode($p, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $programs);
        file_put_contents($dir.'/programs.jsonl.gz', gzencode(implode("\n", $lines)."\n"));
        file_put_contents($dir.'/manifest.json', json_encode([
            'format' => AzadProgramImporter::FORMAT,
            'year' => 1405,
            'booklets' => [],
            'programs' => count($programs),
            'programs_file' => 'programs.jsonl.gz',
            'programs_sha256' => hash_file('sha256', $dir.'/programs.jsonl.gz'),
            'counts' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $dir;
    }

    /** @return array<string, mixed> one programs.jsonl.gz record as tools/azad/build_azad_1405.py writes it */
    private function program(string $unitCode, string $fieldCode, array $overrides = []): array
    {
        return $overrides + [
            'booklet' => 'sarasari',
            'admission' => 'exam',
            'level' => null,
            'province' => 'آذربایجان شرقی',
            'city' => 'سراب',
            'unit_code' => $unitCode,
            'unit_name' => 'واحد سراب',
            'self_funded' => false,
            'field_code' => $fieldCode,
            'field_name' => 'پرستاری',
            'part_time' => false,
            'gender' => 'زن و مرد',
            'exam_group' => 'tajrobi',
            'education_group' => null,
            'capacity_first' => 10,
            'capacity_second' => null,
            'page' => 10,
        ];
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
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

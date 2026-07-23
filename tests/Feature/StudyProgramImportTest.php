<?php

namespace Tests\Feature;

use App\Models\ExamGroup;
use App\Models\ExamYear;
use App\Models\StudyProgram;
use App\Models\StudyProgramReviewRecord;
use App\Services\StudyPrograms\StudyProgramHeaderResolver;
use App\Services\StudyPrograms\StudyProgramImporter;
use App\Services\StudyPrograms\StudyProgramValueMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class StudyProgramImportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_imports_v2_rows_idempotently_updates_changed_rows_and_stages_review_rows(): void
    {
        $file = $this->xlsx([
            $this->mainSheet() => [
                $this->v2Headers(),
                $this->v2Row(['code' => '00123', 'source_hash' => 'hash-one', 'description' => '-']),
            ],
            $this->reviewSheet() => [
                $this->v2Headers(),
                $this->v2Row([
                    'code' => '00456',
                    'admission_type_slug' => 'unknown',
                    'validation_status' => $this->needsReview(),
                    'review_reason' => $this->fa('نامشخص بودن نحوه پذیرش'),
                    'source_hash' => 'review-hash',
                ]),
            ],
        ]);

        $importer = app(StudyProgramImporter::class);

        $first = $importer->importFile($file, 1404, 'riazi', ['force' => true, 'chunk' => 500]);
        $second = $importer->importFile($file, 1404, 'riazi', ['force' => true, 'chunk' => 500]);

        $this->assertSame(1, $first['inserted']);
        $this->assertSame(1, $first['review_rows']);
        $this->assertSame(1, $second['unchanged']);
        $this->assertSame(1, StudyProgram::query()->count());
        $this->assertSame(1, StudyProgramReviewRecord::query()->count());
        $this->assertSame('00123', StudyProgram::query()->where('code', '00123')->value('code'));
        $this->assertNull(StudyProgram::query()->where('code', '00123')->value('description'));

        $changed = $this->xlsx([
            $this->mainSheet() => [
                $this->v2Headers(),
                $this->v2Row([
                    'code' => '00123',
                    'source_hash' => 'hash-two',
                    'description' => $this->fa('توضیح اصلاح شده'),
                ]),
            ],
        ], 'changed-v2.xlsx');

        $updated = $importer->importFile($changed, 1404, 'riazi', ['force' => true, 'chunk' => 500]);

        $this->assertSame(1, $updated['updated']);
        $this->assertSame(1, StudyProgram::query()->count());
        $this->assertSame($this->fa('توضیح اصلاح شده'), StudyProgram::query()->where('code', '00123')->value('description'));
    }

    #[Test]
    public function it_allows_the_same_code_in_different_groups_and_years(): void
    {
        $year1404 = ExamYear::query()->create(['year' => 1404]);
        $year1405 = ExamYear::query()->create(['year' => 1405]);
        $riazi = ExamGroup::query()->create(['name' => $this->fa('ریاضی'), 'slug' => 'riazi']);
        $tajrobi = ExamGroup::query()->create(['name' => $this->fa('تجربی'), 'slug' => 'tajrobi']);

        StudyProgram::query()->create($this->program($year1404->id, $riazi->id, '12345'));
        StudyProgram::query()->create($this->program($year1404->id, $tajrobi->id, '12345'));
        StudyProgram::query()->create($this->program($year1405->id, $riazi->id, '12345'));

        $this->assertSame(3, StudyProgram::query()->where('code', '12345')->count());
    }

    private function program(int $yearId, int $groupId, string $code): array
    {
        $identity = implode('|', [$yearId, $groupId, $code]);

        return [
            'exam_year_id' => $yearId,
            'exam_group_id' => $groupId,
            'code' => $code,
            'source_file' => 'fixture.xlsx',
            'source_hash' => hash('sha256', 'source|'.$identity),
            'identity_hash' => hash('sha256', $identity),
            'validation_status' => 'validated',
        ];
    }

    private function v2Headers(): array
    {
        return array_map(fn (array $aliases) => $aliases[0], StudyProgramHeaderResolver::V2);
    }

    private function v2Row(array $overrides = []): array
    {
        $defaults = [
            'row_index' => '1',
            'code' => '00123',
            'exam_group' => StudyProgramValueMapper::EXAM_GROUPS['riazi'],
            'province' => $this->fa('گیلان'),
            'city' => $this->fa('رشت'),
            'course_type_name' => StudyProgramValueMapper::COURSE_TYPES['day'],
            'course_type_slug' => 'day',
            'original_course_type' => StudyProgramValueMapper::COURSE_TYPES['day'],
            'academic_field' => $this->fa('مهندسی کامپیوتر'),
            'institution' => $this->fa('دانشگاه گیلان'),
            'campus' => null,
            'admission_type_name' => StudyProgramValueMapper::ADMISSION_TYPES['with_exam'],
            'admission_type_slug' => 'with_exam',
            'accepts_male' => $this->fa('مرد'),
            'accepts_female' => $this->fa('زن'),
            'first_capacity' => '10',
            'second_capacity' => '0',
            'description' => null,
            'booklet_page' => '42',
            'booklet_section' => 'A',
            'city_detection_method' => $this->fa('مرجع'),
            'source_file' => 'fixture.pdf',
            'validation_status' => $this->approved(),
            'review_reason' => null,
            'source_hash' => 'hash-one',
        ];

        $row = $overrides + $defaults;

        return array_map(fn (string $key) => $row[$key] ?? null, array_keys(StudyProgramHeaderResolver::V2));
    }

    private function mainSheet(): string
    {
        return $this->fa('کدرشته‌ها');
    }

    private function reviewSheet(): string
    {
        return $this->fa('نیازمند بررسی');
    }

    private function approved(): string
    {
        return $this->fa('تأیید ساختاری');
    }

    private function needsReview(): string
    {
        return $this->fa('نیازمند بررسی');
    }

    private function fa(string $value): string
    {
        return $value;
    }

    private function xlsx(array $sheets, string $name = 'study-programs-v2.xlsx'): string
    {
        $path = storage_path('framework/testing/'.$name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $this->workbookXml(array_keys($sheets)));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml(count($sheets)));

        $index = 1;
        foreach ($sheets as $rows) {
            $zip->addFromString("xl/worksheets/sheet{$index}.xml", $this->sheetXml($rows));
            $index++;
        }

        $zip->close();

        return $path;
    }

    private function contentTypes(int $sheetCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return $xml.'</Types>';
    }

    private function workbookXml(array $sheetNames): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';

        foreach (array_values($sheetNames) as $index => $sheetName) {
            $number = $index + 1;
            $xml .= '<sheet name="'.htmlspecialchars($sheetName, ENT_XML1 | ENT_COMPAT, 'UTF-8').'" sheetId="'.$number.'" r:id="rId'.$number.'"/>';
        }

        return $xml.'</sheets></workbook>';
    }

    private function workbookRelsXml(int $sheetCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $xml .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
        }

        return $xml.'</Relationships>';
    }

    private function sheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="'.($rowIndex + 1).'">';

            foreach (array_values($row) as $columnIndex => $value) {
                $xml .= '<c r="'.$this->columnName($columnIndex).($rowIndex + 1).'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private function columnName(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod).$name;
            $index = intdiv($index - $mod, 26);
        }

        return $name;
    }
}

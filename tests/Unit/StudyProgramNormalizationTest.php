<?php

namespace Tests\Unit;

use App\Services\StudyPrograms\PersianTextNormalizer;
use App\Services\StudyPrograms\StudyProgramHeaderResolver;
use App\Services\StudyPrograms\StudyProgramValueMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StudyProgramNormalizationTest extends TestCase
{
    private PersianTextNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new PersianTextNormalizer();
    }

    #[Test]
    public function it_normalizes_persian_lookup_text(): void
    {
        $this->assertSame(
            "\u{062F}\u{0627}\u{0646}\u{0634}\u{06AF}\u{0627}\u{0647} \u{067E}\u{06CC}\u{0627}\u{0645} \u{0646}\u{0648}\u{0631}",
            $this->normalizer->lookup("\u{062F}\u{0627}\u{0646}\u{0634}\u{06AF}\u{0627}\u{0647} \u{067E}\u{064A}\u{0627}\u{0645}\u{200C}\u{0646}\u{0648}\u{0631}\n")
        );

        $this->assertSame("\u{06A9}\u{06CC}\u{0634}", $this->normalizer->lookup("\u{0643}\u{064A}\u{0634}"));
        $this->assertNull($this->normalizer->nullable('-'));
    }

    #[Test]
    public function it_maps_v2_lookup_slugs_and_exam_groups(): void
    {
        $mapper = new StudyProgramValueMapper($this->normalizer);

        $this->assertSame('riazi', $mapper->examGroupSlug("\u{0631}\u{06CC}\u{0627}\u{0636}\u{06CC} \u{0648} \u{0641}\u{0646}\u{06CC}"));
        $this->assertSame('tajrobi', $mapper->examGroupSlug("\u{0639}\u{0644}\u{0648}\u{0645} \u{062A}\u{062C}\u{0631}\u{0628}\u{06CC}"));
        $this->assertSame("\u{0631}\u{0648}\u{0632}\u{0627}\u{0646}\u{0647}", $mapper->courseTypeName('day'));
        $this->assertSame("\u{0633}\u{0648}\u{0627}\u{0628}\u{0642} \u{062A}\u{062D}\u{0635}\u{06CC}\u{0644}\u{06CC}", $mapper->admissionTypeName('academic_records'));
        $this->assertSame("\u{06AF}\u{06CC}\u{0644}\u{0627}\u{0646}", $mapper->validProvince("\u{06AF}\u{06CC}\u{0644}\u{0627}\u{0646}"));
        $this->assertNull($mapper->validProvince("\u{0627}\u{0633}\u{062A}\u{0627}\u{0646} \u{06AF}\u{06CC}\u{0644}\u{0627}\u{0646}"));
    }

    #[Test]
    public function it_detects_exam_group_from_v2_filename(): void
    {
        $mapper = new StudyProgramValueMapper($this->normalizer);

        $this->assertSame('tajrobi', $mapper->examGroupFromFilename('tajrobi_study_programs_clean_v2.xlsx'));
        $this->assertSame('riazi', $mapper->examGroupFromFilename('RIAZI_study_programs_clean_v2.xlsx'));
        $this->assertNull($mapper->examGroupFromFilename('unknown.xlsx'));
    }

    #[Test]
    public function it_resolves_v2_header_aliases(): void
    {
        $resolver = new StudyProgramHeaderResolver($this->normalizer);
        $map = $resolver->resolve([
            "\u{0631}\u{062F}\u{06CC}\u{0641}",
            "\u{06A9}\u{062F}\u{0631}\u{0634}\u{062A}\u{0647} \u{0645}\u{062D}\u{0644}",
            "\u{06AF}\u{0631}\u{0648}\u{0647} \u{0622}\u{0632}\u{0645}\u{0627}\u{06CC}\u{0634}\u{06CC}",
            "\u{0627}\u{0633}\u{062A}\u{0627}\u{0646}",
            "\u{0634}\u{0647}\u{0631}",
            "\u{0646}\u{0648}\u{0639} \u{062F}\u{0648}\u{0631}\u{0647}",
            "\u{0634}\u{0646}\u{0627}\u{0633}\u{0647} \u{0646}\u{0648}\u{0639} \u{062F}\u{0648}\u{0631}\u{0647}",
            "\u{0631}\u{0634}\u{062A}\u{0647}",
            "\u{062F}\u{0627}\u{0646}\u{0634}\u{06AF}\u{0627}\u{0647} / \u{0645}\u{0624}\u{0633}\u{0633}\u{0647}",
            "\u{0646}\u{062D}\u{0648}\u{0647} \u{067E}\u{0630}\u{06CC}\u{0631}\u{0634}",
            "\u{0634}\u{0646}\u{0627}\u{0633}\u{0647} \u{0646}\u{062D}\u{0648}\u{0647} \u{067E}\u{0630}\u{06CC}\u{0631}\u{0634}",
            "\u{0648}\u{0636}\u{0639}\u{06CC}\u{062A} \u{06A9}\u{0646}\u{062A}\u{0631}\u{0644}",
        ], StudyProgramHeaderResolver::V2, ['code', 'institution', 'admission_type_slug', 'validation_status']);

        $this->assertSame(1, $map['code']);
        $this->assertSame(8, $map['institution']);
        $this->assertSame(10, $map['admission_type_slug']);
    }
}

<?php

namespace Tests\Unit;

use App\Support\PersianDate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PersianDateTest extends TestCase
{
    #[Test]
    public function it_formats_gregorian_dates_for_display_as_jalali(): void
    {
        $this->assertSame('۱۴۰۵/۰۱/۰۱', PersianDate::date('2026-03-21'));
        $this->assertSame('۱۴۰۵/۰۱/۰۱ - ۱۴:۳۰', PersianDate::dateTime('2026-03-21 14:30:00'));
    }

    #[Test]
    public function it_converts_jalali_input_dates_to_gregorian_for_storage(): void
    {
        $this->assertSame('2026-03-21', PersianDate::toGregorianDate('۱۴۰۵/۰۱/۰۱'));
        $this->assertSame('2026-07-07 09:15', PersianDate::toGregorianDateTime('1405/04/16 09:15'));
    }

    #[Test]
    public function it_renders_stored_gregorian_values_back_as_jalali_inputs(): void
    {
        $this->assertSame('۱۴۰۵/۰۴/۱۶', PersianDate::inputDate('2026-07-07'));
        $this->assertSame('۱۴۰۵/۰۴/۱۶ ۰۹:۱۵', PersianDate::inputDateTime('2026-07-07 09:15:00'));
    }
}

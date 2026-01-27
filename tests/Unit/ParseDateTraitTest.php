<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;
use App\Traits\ParseDateTrait;
use App\Exceptions\InvalidDateRangeException;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ParseDateTraitTest extends TestCase
{
    use ParseDateTrait;

    // run this test
    // php artisan test --filter=ParseDateTraitTest

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2025, 10, 20, 12, 0, 0)); // Freeze time for predictable tests
    }

    public function test_default_today_range()
    {
        $result = $this->parseDateRange('');
        $this->assertEquals('2025-10-20 00:00:00', $result['start']);
        $this->assertEquals('2025-10-20 23:59:59', $result['end']);
    }

    public function test_single_date_without_time()
    {
        $result = $this->parseDateRange('2025-09-15');
        $this->assertEquals('2025-09-15 00:00:00', $result['start']);
        $this->assertEquals('2025-09-15 23:59:59', $result['end']);
    }

    public function test_single_datetime()
    {
        $result = $this->parseDateRange('2025-09-15 14:30:00');
        $this->assertEquals('2025-09-15 00:00:00', $result['start']);
        $this->assertEquals('2025-09-15 23:59:59', $result['end']);
    }

    public function test_date_range_with_only_dates()
    {
        $result = $this->parseDateRange('2025-09-01 - 2025-09-07');
        $this->assertEquals('2025-09-01 00:00:00', $result['start']);
        $this->assertEquals('2025-09-07 23:59:59', $result['end']);
    }

    public function test_date_range_with_datetimes()
    {
        $result = $this->parseDateRange('2025-09-01 10:00:00 - 2025-09-07 18:30:00');
        $this->assertEquals('2025-09-01 10:00:00', $result['start']);
        $this->assertEquals('2025-09-07 18:30:00', $result['end']);

        $result = $this->parseDateRange('2025-09-01 11:11:11 - 2025-09-07 22:22:22');
        $this->assertEquals('2025-09-01 11:11:11', $result['start']);
        $this->assertEquals('2025-09-07 22:22:22', $result['end']);
    }

    public function test_invalid_format_throws_exception()
    {
        $this->expectException(InvalidDateRangeException::class);
        $this->parseDateRange('invalid-date-format');
    }

    public function test_end_before_start_throws_exception()
    {
        $this->expectException(InvalidDateRangeException::class);
        $this->parseDateRange('2025-10-10 - 2025-10-01');
    }

    public function test_range_with_extra_spaces()
    {
        $result = $this->parseDateRange(' 2025-09-01  -  2025-09-07 ');
        $this->assertEquals('2025-09-01 00:00:00', $result['start']);
        $this->assertEquals('2025-09-07 23:59:59', $result['end']);
    }
    public function test_single_date_with_extra_spaces()
    {
        $result = $this->parseDateRange(' 2025-09-15 ');
        $this->assertEquals('2025-09-15 00:00:00', $result['start']);
        $this->assertEquals('2025-09-15 23:59:59', $result['end']);
    }
    public function test_it_parses_excel_numeric_date()
    {
        $excelNumber = 45177; // corresponds to 2023-09-10 (depending on Excel epoch)
        $dt = Date::excelToDateTimeObject($excelNumber);
        $expected = $dt->format('Y-m-d H:i:s');

        $result = $this->parseDate($excelNumber);
        $this->assertEquals($expected, $result);
    }
    public function test_it_returns_fallback_for_invalid_string()
    {
        $fallback = '1970-01-01 00:00:00';
        $value = 'invalid-date';
        $result = $this->parseDate($value, $fallback);
        $this->assertEquals($fallback, $result);
    }
    public function test_it_returns_fallback_for_empty_string()
    {
        $fallback = '1970-01-01 00:00:00';
        $result = $this->parseDate('', $fallback);
        $this->assertEquals($fallback, $result);
    }
    public function test_it_returns_fallback_for_null_value()
    {
        $fallback = '1970-01-01 00:00:00';
        $result = $this->parseDate(null, $fallback);
        $this->assertEquals($fallback, $result);
    }
    public function test_it_parses_standard_datetime_string()
    {
        $value = '2025-11-17 07:25:09';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-17 07:25:09', $result);
    }
    public function test_it_parses_datetime_with_microseconds()
    {
        $value = '2025-11-17 07:25:09.123';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-17 07:25:09', $result);
    }
    public function test_it_parses_mm_dd_yyyy_format()
    {
        $value = '11/17/2025 07:25:09';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-17 07:25:09', $result);
    }
    public function test_it_parses_dd_mm_yyyy_format()
    {
        $value = '17/11/2025 07:25:09';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-17 07:25:09', $result);
    }
    public function test_it_parses_datetime_object()
    {
        $dt = Carbon::create(2025, 11, 17, 7, 25, 9);
        $result = $this->parseDate($dt);
        $this->assertEquals('2025-11-17 07:25:09', $result);
    }

    public function test_it_parses_Y_m_d_H_i_s()
    {
        $value = '2025-11-10 02:20:19';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-10 02:20:19', $result);
    }

    public function test_it_parses_Y_m_d_H_i_s_u()
    {
        $value = '2025-11-10 02:20:19.000';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-10 02:20:19', $result);
    }

    public function test_it_parses_Y_m_d_H_i()
    {
        $value = '2025-11-10 02:20';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-10 02:20:00', $result);
    }

    public function test_it_parses_j_M_Y_H_i_s()
    {
        $value = '1-Nov-2025 00:00:00';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-01 00:00:00', $result);
    }

    public function test_it_parses_j_M_Y_H_i_s_u()
    {
        $value = '1-Nov-2025 00:00:00.00';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-01 00:00:00', $result);
    }

    public function test_it_parses_j_M_Y_H_i()
    {
        $value = '1-Nov-2025 00:00';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-01 00:00:00', $result);
    }

    public function test_it_parses_d_m_Y_H_i_s()
    {
        $value = '10/11/2025 02:20:19';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-10-11 02:20:19', $result);
    }

    public function test_it_parses_d_m_Y_H_i()
    {
        $value = '10/11/2025 02:20';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-10-11 02:20:00', $result);
    }

    public function test_it_parses_m_d_Y_H_i_s()
    {
        $value = '11/10/2025 02:20:19';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-10 02:20:19', $result);
    }

    public function test_it_parses_m_d_Y_H_i_s_u()
    {
        $value = '11/10/2025 02:20:19.000';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-10 02:20:19', $result);
    }

    public function test_it_parses_m_d_Y_H_i()
    {
        $value = '11/10/2025 02:20';
        $result = $this->parseDate($value);
        $this->assertEquals('2025-11-10 02:20:00', $result);
    }

    public function test_it_returns_fallback_for_invalid_value()
    {
        $value = 'invalid-date';
        $fallback = '1970-01-01 00:00:00';
        $result = $this->parseDate($value, $fallback);
        $this->assertEquals($fallback, $result);
    }

    public function test_it_parses_DateTime_instance()
    {
        $dt = Carbon::create(2025, 11, 10, 2, 20, 19);
        $result = $this->parseDate($dt);
        $this->assertEquals('2025-11-10 02:20:19', $result);
    }

    public function test_it_parses_excel_numeric_value()
    {
        $excelNumber = 45177; // corresponds to 2023-08-11 depending on Excel epoch
        $dt = Date::excelToDateTimeObject($excelNumber);
        $expected = $dt->format('Y-m-d H:i:s');

        $result = $this->parseDate($excelNumber);
        $this->assertEquals($expected, $result);
    }

    public function test_it_parses_misspelled_month_names()
    {
        $value = 'Junuary 26, 2026';

        $result = $this->parseDate($value);

        $this->assertSame(
            '2026-01-26 00:00:00',
            $result
        );
    }

    public function test_parse_date_invalid_value()
    {
        $value = '\u00a0';
        $result = $this->parseDate($value);
        $this->assertNull($result);
    }
}

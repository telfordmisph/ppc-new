<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;
use App\Traits\ParseDateTrait;
use App\Exceptions\InvalidDateRangeException;

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
}

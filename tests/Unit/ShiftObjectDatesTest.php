<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Collection;
use App\Traits\ShiftObjectDates;

class ShiftObjectDatesTest extends TestCase
{
  use ShiftObjectDates;

  public function test_it_returns_trend_unchanged_when_period_is_not_daily()
  {
    $trend = collect([
      (object) ['day' => '2024-01-10'],
    ]);

    $result = $this->shiftOneDayBack($trend, 'monthly');

    $this->assertSame($trend, $result);
    $this->assertEquals('2024-01-10', $trend[0]->day);
  }

  public function test_it_shifts_day_back_for_collection_of_objects()
  {
    $trend = collect([
      (object) ['day' => '2024-01-10'],
      (object) ['day' => '2024-01-05'],
    ]);

    $this->shiftOneDayBack($trend, 'daily');

    $this->assertEquals('2024-01-09', $trend[0]->day);
    $this->assertEquals('2024-01-04', $trend[1]->day);
  }

  public function test_it_shifts_day_back_for_array_of_objects()
  {
    $trend = [
      (object) ['day' => '2024-01-10'],
      (object) ['day' => '2024-01-05'],
    ];

    $result = $this->shiftOneDayBack($trend, 'daily');

    $this->assertEquals('2024-01-09', $result[0]->day);
    $this->assertEquals('2024-01-04', $result[1]->day);
  }

  public function test_it_shifts_day_back_for_collection_of_arrays()
  {
    $trend = new Collection([
      ['day' => '2024-01-10'],
      ['day' => '2024-01-05'],
    ]);

    $this->shiftOneDayBack($trend, 'daily');

    $this->assertEquals('2024-01-04', $trend[1]['day']);
    $this->assertEquals('2024-01-09', $trend[0]['day']);
  }

  public function test_it_shifts_day_back_for_array_of_arrays()
  {
    $trend = [
      ['day' => '2024-01-10'],
      ['day' => '2024-01-05'],
    ];

    $result = $this->shiftOneDayBack($trend, 'daily');

    $this->assertEquals('2024-01-09', $result[0]['day']);
    $this->assertEquals('2024-01-04', $result[1]['day']);
  }

  public function test_it_does_not_fail_when_day_is_missing()
  {
    $trend = collect([
      (object) ['foo' => 'bar'],
      ['baz' => 'qux'],
    ]);

    $this->shiftOneDayBack($trend, 'daily');

    $this->assertTrue(true); // test passes if no exception is thrown
  }
}

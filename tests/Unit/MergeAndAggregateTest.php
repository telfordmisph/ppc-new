<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Helpers\MergeAndAggregate;

class MergeAndAggregateTest extends TestCase
{
  public function test_it_merges_and_sums_numeric_fields_by_single_group_key()
  {
    $data1 = [
      ['day' => '2025-11-06', 'total_quantity' => 100, 'remarks' => 'A'],
      ['day' => '2025-11-07', 'total_quantity' => 200, 'remarks' => 'B'],
    ];

    $data2 = [
      ['day' => '2025-11-06', 'total_quantity' => 50, 'remarks' => 'C'],
    ];

    $result = MergeAndAggregate::mergeAndAggregate([$data1, $data2], 'day');

    $this->assertCount(2, $result);
    $this->assertEquals(150, $result[0]['total_quantity']);
    $this->assertEquals('2025-11-06', $result[0]['day']);
  }

  public function test_it_merges_by_multiple_keys_and_sums_correctly()
  {
    $f1 = [
      ['production_line' => 'PL6', 'day' => '2025-10-22', 'total_quantity' => 2113398],
      ['production_line' => 'PL6', 'day' => '2025-10-23', 'total_quantity' => 1740507],
    ];

    $f2 = [
      ['production_line' => 'PL6', 'day' => '2025-10-22', 'total_quantity' => 72323],
      ['production_line' => 'PL6', 'day' => '2025-10-23', 'total_quantity' => 124087],
    ];

    $result = MergeAndAggregate::mergeAndAggregate([$f1, $f2], ['production_line', 'day']);

    $this->assertCount(2, $result);
    $this->assertEquals(2185721, $result[0]['total_quantity']);
    $this->assertEquals(1864594, $result[1]['total_quantity']);
  }

  public function test_it_ignores_specified_fields_during_merge()
  {
    $data1 = [
      ['day' => '2025-11-06', 'total_quantity' => 100, 'ignore_me' => 5],
    ];

    $data2 = [
      ['day' => '2025-11-06', 'total_quantity' => 200, 'ignore_me' => 10],
    ];

    $result = MergeAndAggregate::mergeAndAggregate([$data1, $data2], 'day', ['ignore_me']);

    $this->assertEquals(300, $result[0]['total_quantity']);
    $this->assertEquals(5, $result[0]['ignore_me']);
  }

  public function test_it_handles_non_numeric_fields_gracefully()
  {
    $data1 = [
      ['day' => '2025-11-06', 'total_quantity' => 100, 'status' => 'ok'],
    ];

    $data2 = [
      ['day' => '2025-11-06', 'total_quantity' => 50, 'status' => 'ok'],
    ];

    $result = MergeAndAggregate::mergeAndAggregate([$data1, $data2], 'day');

    $this->assertEquals(150, $result[0]['total_quantity']);
    $this->assertEquals('ok', $result[0]['status']);
  }

  public function test_it_returns_empty_array_when_no_data()
  {
    $result = MergeAndAggregate::mergeAndAggregate([], 'day');
    $this->assertEquals([], $result);
  }

  public function test_it_returns_non_empty_array_while_others_are_empty()
  {
    $data1 = [
      ['day' => '2025-11-06', 'total_quantity' => 100, 'status' => 'ok'],
    ];

    $data_none = [];
    $data_none_2 = [];

    $result = MergeAndAggregate::mergeAndAggregate([$data1, $data_none, $data_none_2], 'day');
    $this->assertEquals($data1, $result);
  }
}

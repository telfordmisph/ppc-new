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
      ['day' => '2025-11-06', 'total_wip' => 100, 'remarks' => 'A'],
      ['day' => '2025-11-07', 'total_wip' => 200, 'remarks' => 'B'],
    ];

    $data2 = [
      ['day' => '2025-11-06', 'total_wip' => 50, 'remarks' => 'C'],
    ];

    $result = MergeAndAggregate::mergeAndAggregate([$data1, $data2], 'day');

    $this->assertCount(2, $result);
    $this->assertEquals(150, $result[0]['total_wip']);
    $this->assertEquals('2025-11-06', $result[0]['day']);
    $this->assertEquals(200, $result[1]['total_wip']);

    // remarks overwritten by last occurrence
    $this->assertEquals('C', $result[0]['remarks']);
  }

  public function test_it_merges_by_multiple_keys_and_sums_correctly()
  {
    $f1 = [
      ['production_line' => 'PL6', 'day' => '2025-10-22', 'total_wip' => 2113398],
      ['production_line' => 'PL6', 'day' => '2025-10-23', 'total_wip' => 1740507],
    ];

    $f2 = [
      ['production_line' => 'PL6', 'day' => '2025-10-22', 'total_wip' => 72323],
      ['production_line' => 'PL6', 'day' => '2025-10-23', 'total_wip' => 124087],
    ];

    $result = MergeAndAggregate::mergeAndAggregate([$f1, $f2], ['production_line', 'day']);

    $this->assertCount(2, $result);
    $this->assertEquals(2185721, $result[0]['total_wip']);
    $this->assertEquals(1864594, $result[1]['total_wip']);
  }

  public function test_it_ignores_specified_fields_during_merge()
  {
    $data1 = [
      ['day' => '2025-11-06', 'total_wip' => 100, 'ignore_me' => 5],
    ];

    $data2 = [
      ['day' => '2025-11-06', 'total_wip' => 200, 'ignore_me' => 10],
    ];

    $result = MergeAndAggregate::mergeAndAggregate([$data1, $data2], 'day', ['ignore_me']);

    $this->assertEquals(300, $result[0]['total_wip']);
    $this->assertEquals(5, $result[0]['ignore_me']);
  }

  public function test_it_handles_non_numeric_fields_gracefully()
  {
    $data1 = [
      ['day' => '2025-11-06', 'total_wip' => 100, 'status' => 'ok'],
    ];

    $data2 = [
      ['day' => '2025-11-06', 'total_wip' => '50', 'status' => 'ok'],
    ];

    $result = MergeAndAggregate::mergeAndAggregate([$data1, $data2], 'day');

    $this->assertEquals(150, $result[0]['total_wip']);
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
      ['day' => '2025-11-06', 'total_wip' => 100, 'status' => 'ok'],
    ];

    $data_none = [];
    $data_none_2 = [];

    $result = MergeAndAggregate::mergeAndAggregate([$data1, $data_none, $data_none_2], 'day');
    $this->assertEquals($data1, $result);
  }

  public function testMergeAndAggregateWithCollections()
  {
    $dataset1 = collect([
      ['id' => 1, 'group' => 'A', 'value' => 10],
      ['id' => 2, 'group' => 'B', 'value' => 20],
    ]);

    $dataset2 = collect([
      ['id' => 3, 'group' => 'A', 'value' => 5],
      ['id' => 4, 'group' => 'B', 'value' => 15],
    ]);

    $result = MergeAndAggregate::mergeAndAggregate([$dataset1, $dataset2], ['group'], ['id']);

    $expected = [
      ['id' => 1, 'group' => 'A', 'value' => 15], // 10 + 5
      ['id' => 2, 'group' => 'B', 'value' => 35], // 20 + 15
    ];

    $this->assertEquals($expected, $result);
  }

  public function testFactoryTrends()
  {
    $data1 = [
      [
        "year" => 2025,
        "month" => 7,
        "total_wip" => "1809399"
      ],
      [
        "year" => 2025,
        "month" => 8,
        "total_wip" => "2113442"
      ],
      [
        "year" => 2025,
        "month" => 9,
        "total_wip" => "1345601"
      ],
      [
        "year" => 2025,
        "month" => 10,
        "total_wip" => "533872"
      ],
      [
        "year" => 2025,
        "month" => 11,
        "total_wip" => "1019285"
      ]
    ];

    $data2 = [
      [
        "year" => 2025,
        "month" => 8,
        "total_wip" => "10683521"
      ],
      [
        "year" => 2025,
        "month" => 9,
        "total_wip" => "24319695"
      ],
      [
        "year" => 2025,
        "month" => 10,
        "total_wip" => "6977840"
      ],
      [
        "year" => 2025,
        "month" => 11,
        "total_wip" => "16466602"
      ]
    ];

    $data3 = [];

    $result = MergeAndAggregate::mergeAndAggregate([$data1, $data2, $data3], ['year', 'month']);

    $expected = [
      [
        "year" => 2025,
        "month" => 7,
        "total_wip" => 1809399
      ],
      [
        "year" => 2025,
        "month" => 8,
        "total_wip" => 2113442 + 10683521
      ],
      [
        "year" => 2025,
        "month" => 9,
        "total_wip" => 1345601 + 24319695
      ],
      [
        "year" => 2025,
        "month" => 10,
        "total_wip" => 533872 + 6977840
      ],
      [
        "year" => 2025,
        "month" => 11,
        "total_wip" => 1019285 + 16466602
      ]
    ];

    $this->assertEquals($expected, $result);
  }
}

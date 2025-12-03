<?php

use PHPUnit\Framework\TestCase;
use App\Helpers\WipTrendParser;

class TrendParserTest extends TestCase
{
  public function test_ParseDailyTrend()
  {
    $input = [
      'FactoryA' => [
        ['day' => '2025-11-24', 'qty' => 10, 'weight' => 5],
      ],
    ];

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FactoryA_qty' => 10,
        'FactoryA_weight' => 5,
      ]
    ];

    $this->assertEquals($expected, WipTrendParser::parseTrendsByPeriod($input));
  }

  public function test_ParseWeeklyTrend()
  {
    $input = [
      'FactoryB' => [
        ['week' => 48, 'year' => 2025, 'qty' => 15],
      ],
    ];

    $expected = [
      [
        'dateKey' => '2025-W48',
        'label' => 'Week 48, 2025',
        'FactoryB_qty' => 15,
      ]
    ];

    $this->assertEquals($expected, WipTrendParser::parseTrendsByPeriod($input));
  }

  public function test_ParseMonthlyTrend()
  {
    $input = [
      'FactoryA' => [
        ['month' => 11, 'year' => 2025, 'qty' => 20],
      ],
    ];

    $expected = [
      [
        'dateKey' => '2025-11',
        'label' => 'Nov 2025',
        'FactoryA_qty' => 20,
      ]
    ];

    $this->assertEquals($expected, WipTrendParser::parseTrendsByPeriod($input));
  }

  public function test_ParseQuarterlyTrend()
  {
    $input = [
      'FactoryA' => [
        ['quarter' => 4, 'year' => 2025, 'qty' => 50],
      ],
    ];

    $expected = [
      [
        'dateKey' => '2025-Q4',
        'label' => 'Q4 2025',
        'FactoryA_qty' => 50,
      ]
    ];

    $this->assertEquals($expected, WipTrendParser::parseTrendsByPeriod($input));
  }

  public function test_ParseYearlyTrend()
  {
    $input = [
      'FactoryB' => [
        ['year' => 2025, 'qty' => 100],
      ],
    ];

    $expected = [
      [
        'dateKey' => '2025',
        'label' => '2025',
        'FactoryB_qty' => 100,
      ]
    ];

    $this->assertEquals($expected, WipTrendParser::parseTrendsByPeriod($input));
  }

  public function test_MergeMultipleFactories()
  {
    $input = [
      'FactoryA' => [
        ['day' => '2025-11-24', 'qty' => 10],
      ],
      'FactoryB' => [
        ['day' => '2025-11-24', 'qty' => 5],
      ],
    ];

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FactoryA_qty' => 10,
        'FactoryB_qty' => 5,
      ]
    ];

    $this->assertEquals($expected, WipTrendParser::parseTrendsByPeriod($input));
  }

  public function test_SortingByDate()
  {
    $input = [
      'FactoryA' => [
        ['day' => '2025-11-25', 'qty' => 10],
        ['day' => '2025-11-24', 'qty' => 5],
      ],
    ];

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FactoryA_qty' => 5,
      ],
      [
        'dateKey' => '2025-11-25',
        'label' => 'Nov 25',
        'FactoryA_qty' => 10,
      ],
    ];

    $this->assertEquals($expected, WipTrendParser::parseTrendsByPeriod($input));
  }

  public function test_IgnoresNonNumericFields()
  {
    $input = [
      'FactoryA' => [
        ['day' => '2025-11-24', 'qty' => 10, 'note' => 'ignore me'],
      ],
    ];

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FactoryA_qty' => 10,
      ]
    ];

    $this->assertEquals($expected, WipTrendParser::parseTrendsByPeriod($input));
  }

  public function test_CustomMetricFilter()
  {
    $input = [
      'FactoryA' => [
        ['day' => '2025-11-24', 'qty' => 10, 'note' => 'important'],
      ],
    ];

    $metricFilter = fn($field, $value) => $field === 'note';

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FactoryA_note' => 'important',
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, [], ['day'], $metricFilter)
    );
  }

  public function test_AllThreeOptions()
  {
    $input = [
      'FactoryX' => [
        ['day' => '2025-11-24', 'qty' => 10, 'weight' => 5, 'note' => 'important'],
      ],
    ];

    $prefixMap = ['FactoryX' => 'FX'];
    $periodFields = ['day']; // only day
    $metricFilter = fn($field, $value) => $field !== 'weight'; // exclude weight

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FX_qty' => 10,
        'FX_note' => 'important',
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, $prefixMap, $periodFields, $metricFilter)
    );
  }

  /** Test with prefixMap only */
  public function test_WithPrefixMapOnly()
  {
    $input = [
      'FactoryY' => [
        ['day' => '2025-11-24', 'qty' => 10],
      ],
    ];

    $prefixMap = ['FactoryY' => 'FY'];

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FY_qty' => 10,
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, $prefixMap)
    );
  }

  /** Test with custom periodFields only */
  public function test_WithCustomPeriodFieldsOnly()
  {
    $input = [
      'FactoryZ' => [
        ['month' => 11, 'year' => '2025', 'qty' => 20],
      ],
    ];

    $periodFields = ['month', 'year']; // only consider month

    $expected = [
      [
        'dateKey' => '2025-11',
        'label' => 'Nov 2025',
        'FactoryZ_qty' => 20,
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, [], $periodFields)
    );
  }

  /** Test with custom metricFilter only */
  public function test_WithMetricFilterOnly()
  {
    $input = [
      'FactoryA' => [
        ['day' => '2025-11-24', 'qty' => 10, 'note' => 'keep me'],
      ],
    ];

    $metricFilter = fn($field, $value) => $field === 'note';

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FactoryA_note' => 'keep me',
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, [], ['day'], $metricFilter)
    );
  }

  /** Test with prefixMap and custom periodFields only */
  public function test_PrefixMapAndPeriodFields()
  {
    $input = [
      'FactoryB' => [
        ['month' => 11, 'year' => '2025', 'qty' => 30],
        ['month' => 11, 'year' => '2025', 'qty' => 30],
      ],
    ];

    $prefixMap = ['FactoryB' => 'FB'];
    $periodFields = ['month', 'year'];

    $expected = [
      [
        'dateKey' => '2025-11',
        'label' => 'Nov 2025',
        'FB_qty' => 30,
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, $prefixMap, $periodFields)
    );
  }

  /** Test with prefixMap and metricFilter only */
  public function test_PrefixMapAndMetricFilter()
  {
    $input = [
      'FactoryC' => [
        ['day' => '2025-11-24', 'qty' => 10, 'note' => 'important'],
      ],
    ];

    $prefixMap = ['FactoryC' => 'FC'];
    $metricFilter = fn($field, $value) => $field === 'note';

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FC_note' => 'important',
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, $prefixMap, ['day'], $metricFilter)
    );
  }

  /** Test with periodFields and metricFilter only */
  public function test_PeriodFieldsAndMetricFilter()
  {
    $input = [
      'FactoryD' => [
        ['month' => 11, 'year' => 2025, 'qty' => 50, 'note' => 'keep'],
      ],
    ];

    $periodFields = ['month'];
    $metricFilter = fn($field, $value) => $field === 'note';

    $expected = [
      [
        'dateKey' => '2025-11',
        'label' => 'Nov 2025',
        'FactoryD_note' => 'keep',
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, [], $periodFields, $metricFilter)
    );
  }

  /** Test with none of the three (default behavior) */
  public function test_DefaultBehavior()
  {
    $input = [
      'FactoryE' => [
        ['day' => '2025-11-24', 'qty' => 5, 'weight' => 2],
      ],
      'FactoryA' => [
        ['day' => '2025-11-25', 'qty' => 15, 'weight' => 12],
      ],
      'FactoryB' => [
        ['day' => '2025-11-26', 'qty' => 25, 'weight' => 22],
      ],
      'FactoryC' => [
        ['day' => '2025-11-27', 'qty' => 35, 'weight' => 32],
      ],
      'FactoryD' => [
        ['day' => '2025-11-27', 'qty' => 35, 'weight' => 32],
      ],
    ];

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FactoryE_qty' => 5,
        'FactoryE_weight' => 2,
      ],
      [
        'dateKey' => '2025-11-25',
        'label' => 'Nov 25',
        'FactoryA_qty' => 15,
        'FactoryA_weight' => 12,
      ],
      [
        'dateKey' => '2025-11-26',
        'label' => 'Nov 26',
        'FactoryB_qty' => 25,
        'FactoryB_weight' => 22,
      ],
      [
        'dateKey' => '2025-11-27',
        'label' => 'Nov 27',
        'FactoryC_qty' => 35,
        'FactoryC_weight' => 32,
        'FactoryD_qty' => 35,
        'FactoryD_weight' => 32,
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input)
    );
  }

  public function test_DefaultBehavior2nd()
  {
    $input = [
      'f1_trend' => [
        ["workweek" => "w601", "year" => '2025', "week" => "601", 'qty' => 5, 'weight' => 2],
      ],
      'f2_trend' => [
        ["workweek" => "w601", "year" => '2025', "week" => "601", 'qty' => 15, 'weight' => 12],
      ],
      'overall_trend' => [
        ["workweek" => "w601", "year" => '2025', "week" => "601", 'qty' => 25, 'weight' => 22],
      ],
    ];

    // $prefixMap = ['f1_trend' => 'f1', 'f2_trend' => 'f2', 'overall_trend' => 'overall'];

    $expected = [
      [
        'dateKey' => '2025-11-25',
        'label' => 'Nov 25',
        'f1_qty' => 5,
        'f1_weight' => 2,
        'f2_qty' => 15,
        'f2_weight' => 12,
        'overall_qty' => 25,
        'overall_weight' => 22,
      ],
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input)
    );
  }

  public function test_DefaultBehavior3nd()
  {
    $input = [
      'f1_trend' => [
        ['day' => '2025-11-25', 'qty' => 5, 'weight' => 2],
        ['day' => '2025-11-25', 'qty' => 5, 'weight' => 2],
      ],
      'f2_trend' => [
        ['day' => '2025-11-25', 'qty' => 15, 'weight' => 12],
      ],
      'overall_trend' => [
        ['day' => '2025-11-25', 'qty' => 25, 'weight' => 22],
      ],
    ];

    $prefixMap = ['f1_trend' => 'f1', 'f2_trend' => 'f2', 'overall_trend' => 'overall'];

    $expected = [
      [
        'dateKey' => '2025-11-25',
        'label' => 'Nov 25',
        'f1_qty' => 10,
        'f1_weight' => 2,
        'f2_qty' => 15,
        'f2_weight' => 12,
        'overall_qty' => 25,
        'overall_weight' => 22,
      ],
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, $prefixMap)
    );
  }

  public function test_IgnorePrefixMap()
  {
    $input = [
      'FactoryE' => [
        ['day' => '2025-11-24', 'qty' => 5, 'weight' => 2],
      ],
    ];

    $prefixMap = ['unknown' => 'should not be used'];

    $expected = [
      [
        'dateKey' => '2025-11-24',
        'label' => 'Nov 24',
        'FactoryE_qty' => 5,
        'FactoryE_weight' => 2,
      ]
    ];

    $this->assertEquals(
      $expected,
      WipTrendParser::parseTrendsByPeriod($input, $prefixMap)
    );
  }
}

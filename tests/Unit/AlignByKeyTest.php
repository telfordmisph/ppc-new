<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use App\Traits\AlignByKeyTrait;

class AlignByKeyTest extends TestCase
{
  use AlignByKeyTrait;

  // -------------------------------------------------------------------------
  // Basic alignment
  // -------------------------------------------------------------------------

  public function test_fills_missing_keys_in_first_array()
  {
    $a = [
      ['dateKey' => '2026-02-02', 'total_wip' => 100],
      ['dateKey' => '2026-02-04', 'total_wip' => 300],
    ];
    $b = [
      ['dateKey' => '2026-02-02', 'total_outs' => 50],
      ['dateKey' => '2026-02-03', 'total_outs' => 75],
      ['dateKey' => '2026-02-04', 'total_outs' => 80],
    ];

    [$alignedA, $alignedB] = $this->align('dateKey', [], $a, $b);

    $this->assertCount(3, $alignedA);
    $this->assertCount(3, $alignedB);

    $this->assertEquals('2026-02-03', $alignedA[1]['dateKey']);
    $this->assertArrayNotHasKey('total_wip', $alignedA[1]);
  }

  public function test_fills_missing_keys_in_second_array()
  {
    $a = [
      ['dateKey' => '2026-02-01', 'total_wip' => 100],
      ['dateKey' => '2026-02-02', 'total_wip' => 200],
    ];
    $b = [
      ['dateKey' => '2026-02-02', 'total_outs' => 50],
    ];

    [$alignedA, $alignedB] = $this->align('dateKey', [], $a, $b);

    $this->assertCount(2, $alignedA);
    $this->assertCount(2, $alignedB);

    $this->assertEquals('2026-02-01', $alignedB[0]['dateKey']);
    $this->assertArrayNotHasKey('total_outs', $alignedB[0]);
  }

  public function test_both_arrays_have_identical_key_sequences()
  {
    $a = [
      ['dateKey' => '2026-02-01', 'f1_wip' => 100],
      ['dateKey' => '2026-02-03', 'f1_wip' => 300],
    ];
    $b = [
      ['dateKey' => '2026-02-02', 'f1_outs' => 50],
      ['dateKey' => '2026-02-04', 'f1_outs' => 80],
    ];

    [$alignedA, $alignedB] = $this->align('dateKey', [], $a, $b);

    $keysA = array_column($alignedA, 'dateKey');
    $keysB = array_column($alignedB, 'dateKey');

    $this->assertEquals($keysA, $keysB);
    $this->assertEquals(['2026-02-01', '2026-02-02', '2026-02-03', '2026-02-04'], $keysA);
  }

  public function test_result_is_sorted_by_key()
  {
    $a = [
      ['dateKey' => '2026-02-05', 'wip' => 500],
      ['dateKey' => '2026-02-01', 'wip' => 100],
      ['dateKey' => '2026-02-03', 'wip' => 300],
    ];
    $b = [
      ['dateKey' => '2026-02-02', 'outs' => 200],
    ];

    [$alignedA] = $this->align('dateKey', [], $a, $b);

    $keys = array_column($alignedA, 'dateKey');
    $this->assertEquals(['2026-02-01', '2026-02-02', '2026-02-03', '2026-02-05'], $keys);
  }

  // -------------------------------------------------------------------------
  // Inherit fields
  // -------------------------------------------------------------------------

  public function test_gap_row_inherits_label_from_other_array()
  {
    $a = [
      ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'total_wip' => 100],
      ['dateKey' => '2026-02-03', 'label' => 'Feb 3', 'total_wip' => 300],
    ];
    $b = [
      ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'total_outs' => 50],
      ['dateKey' => '2026-02-02', 'label' => 'Feb 2', 'total_outs' => 75],
      ['dateKey' => '2026-02-03', 'label' => 'Feb 3', 'total_outs' => 80],
    ];

    [$alignedA] = $this->align('dateKey', ['label'], $a, $b);

    // Feb 2 was missing from A — should inherit label from B
    $this->assertEquals('Feb 2', $alignedA[1]['label']);
    $this->assertArrayNotHasKey('total_wip', $alignedA[1]);
  }

  public function test_gap_row_inherits_multiple_fields()
  {
    $a = [
      ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'year' => 2026, 'month' => 2, 'total_wip' => 100],
    ];
    $b = [
      ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'year' => 2026, 'month' => 2, 'total_outs' => 50],
      ['dateKey' => '2026-02-02', 'label' => 'Feb 2', 'year' => 2026, 'month' => 2, 'total_outs' => 75],
    ];

    [$alignedA] = $this->align('dateKey', ['label', 'year', 'month'], $a, $b);

    $this->assertEquals('Feb 2', $alignedA[1]['label']);
    $this->assertEquals(2026, $alignedA[1]['year']);
    $this->assertEquals(2, $alignedA[1]['month']);
    $this->assertArrayNotHasKey('total_wip', $alignedA[1]);
  }

  public function test_gap_row_without_inherit_fields_has_only_key()
  {
    $a = [
      ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'total_wip' => 100],
    ];
    $b = [
      ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'total_outs' => 50],
      ['dateKey' => '2026-02-02', 'label' => 'Feb 2', 'total_outs' => 75],
    ];

    [$alignedA] = $this->align('dateKey', [], $a, $b);

    // no inherit fields — gap row should only have dateKey
    $this->assertArrayNotHasKey('label', $alignedA[1]);
    $this->assertEquals('2026-02-02', $alignedA[1]['dateKey']);
  }

  public function test_inherit_uses_first_found_value_across_arrays()
  {
    $a = [
      ['dateKey' => '2026-02-03', 'label' => 'Feb 3 from A', 'total_wip' => 100],
    ];
    $b = [
      ['dateKey' => '2026-02-03', 'label' => 'Feb 3 from B', 'total_outs' => 50],
      ['dateKey' => '2026-02-04', 'label' => 'Feb 4 from B', 'total_outs' => 80],
    ];
    $c = [
      ['dateKey' => '2026-02-02', 'label' => 'Feb 2 from C', 'total_lots' => 10],
    ];

    [,, $alignedC] = $this->align('dateKey', ['label'], $a, $b, $c);

    // A is first so its label wins for Feb 3
    $this->assertEquals('Feb 3 from A', $alignedC[1]['label']);
    $this->assertEquals('Feb 4 from B', $alignedC[2]['label']);
  }

  public function test_inherit_field_not_overwritten_on_existing_rows()
  {
    $a = [
      ['dateKey' => '2026-02-01', 'label' => 'My Custom Label', 'total_wip' => 100],
    ];
    $b = [
      ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'total_outs' => 50],
    ];

    [$alignedA] = $this->align('dateKey', ['label'], $a, $b);

    // existing row label must not be overwritten
    $this->assertEquals('My Custom Label', $alignedA[0]['label']);
  }

  public function test_empty_array_inherits_labels_from_non_empty_array()
  {
    $a = [
      ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'total_wip' => 100],
      ['dateKey' => '2026-02-02', 'label' => 'Feb 2', 'total_wip' => 200],
    ];
    $b = [];

    [$alignedA, $alignedB] = $this->align('dateKey', ['label'], $a, $b);

    $this->assertCount(2, $alignedB);
    $this->assertEquals('Feb 1', $alignedB[0]['label']);
    $this->assertEquals('Feb 2', $alignedB[1]['label']);
    $this->assertArrayNotHasKey('total_wip', $alignedB[0]);
  }

  // -------------------------------------------------------------------------
  // Parameterized key field
  // -------------------------------------------------------------------------

  public function test_works_with_custom_key_field()
  {
    $a = [
      ['weekKey' => 'W10', 'total_wip' => 1000],
      ['weekKey' => 'W12', 'total_wip' => 1200],
    ];
    $b = [
      ['weekKey' => 'W11', 'total_outs' => 800],
      ['weekKey' => 'W12', 'total_outs' => 900],
    ];

    [$alignedA, $alignedB] = $this->align('weekKey', [], $a, $b);

    $this->assertCount(3, $alignedA);
    $this->assertCount(3, $alignedB);

    $this->assertEquals('W11', $alignedA[1]['weekKey']);
    $this->assertArrayNotHasKey('total_wip', $alignedA[1]);
  }

  public function test_works_with_numeric_key_field()
  {
    $a = [
      ['month' => 1, 'total_wip' => 100],
      ['month' => 3, 'total_wip' => 300],
    ];
    $b = [
      ['month' => 2, 'total_outs' => 200],
      ['month' => 3, 'total_outs' => 300],
    ];

    [$alignedA, $alignedB] = $this->align('month', [], $a, $b);

    $this->assertCount(3, $alignedA);
    $this->assertEquals(2, $alignedA[1]['month']);
    $this->assertArrayNotHasKey('total_wip', $alignedA[1]);
  }

  // -------------------------------------------------------------------------
  // Three or more arrays
  // -------------------------------------------------------------------------

  public function test_aligns_three_arrays()
  {
    $a = [['dateKey' => '2026-02-01', 'f1_wip' => 100]];
    $b = [['dateKey' => '2026-02-02', 'f2_wip' => 200]];
    $c = [['dateKey' => '2026-02-03', 'f3_wip' => 300]];

    [$alignedA, $alignedB, $alignedC] = $this->align('dateKey', [], $a, $b, $c);

    $this->assertCount(3, $alignedA);
    $this->assertCount(3, $alignedB);
    $this->assertCount(3, $alignedC);

    $keysA = array_column($alignedA, 'dateKey');
    $keysB = array_column($alignedB, 'dateKey');
    $keysC = array_column($alignedC, 'dateKey');

    $this->assertEquals($keysA, $keysB);
    $this->assertEquals($keysB, $keysC);
  }

  // -------------------------------------------------------------------------
  // Handles objects (stdClass)
  // -------------------------------------------------------------------------

  public function test_handles_array_of_objects()
  {
    $a = [
      (object) ['dateKey' => '2026-02-01', 'total_wip' => 100],
      (object) ['dateKey' => '2026-02-03', 'total_wip' => 300],
    ];
    $b = [
      (object) ['dateKey' => '2026-02-02', 'total_outs' => 200],
    ];

    [$alignedA, $alignedB] = $this->align('dateKey', [], $a, $b);

    $this->assertCount(3, $alignedA);
    $this->assertCount(3, $alignedB);
    $this->assertEquals('2026-02-02', $alignedA[1]['dateKey']);
  }

  public function test_handles_objects_with_inherit_fields()
  {
    $a = [
      (object) ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'total_wip' => 100],
    ];
    $b = [
      (object) ['dateKey' => '2026-02-01', 'label' => 'Feb 1', 'total_outs' => 50],
      (object) ['dateKey' => '2026-02-02', 'label' => 'Feb 2', 'total_outs' => 75],
    ];

    [$alignedA] = $this->align('dateKey', ['label'], $a, $b);

    $this->assertEquals('Feb 2', $alignedA[1]['label']);
    $this->assertArrayNotHasKey('total_wip', $alignedA[1]);
  }

  // -------------------------------------------------------------------------
  // Edge cases
  // -------------------------------------------------------------------------

  public function test_identical_arrays_are_unchanged()
  {
    $a = [
      ['dateKey' => '2026-02-01', 'total_wip' => 100],
      ['dateKey' => '2026-02-02', 'total_wip' => 200],
    ];
    $b = [
      ['dateKey' => '2026-02-01', 'total_outs' => 50],
      ['dateKey' => '2026-02-02', 'total_outs' => 75],
    ];

    [$alignedA, $alignedB] = $this->align('dateKey', [], $a, $b);

    $this->assertCount(2, $alignedA);
    $this->assertCount(2, $alignedB);
    $this->assertEquals(100, $alignedA[0]['total_wip']);
    $this->assertEquals(75, $alignedB[1]['total_outs']);
  }

  public function test_both_empty_returns_two_empty_arrays()
  {
    [$alignedA, $alignedB] = $this->align('dateKey', [], [], []);

    $this->assertEquals([], $alignedA);
    $this->assertEquals([], $alignedB);
  }

  public function test_existing_data_is_preserved_after_alignment()
  {
    $a = [
      ['dateKey' => '2026-02-01', 'f1_wip' => 999, 'f1_lots' => 42],
    ];
    $b = [
      ['dateKey' => '2026-02-01', 'f1_outs' => 500],
      ['dateKey' => '2026-02-02', 'f1_outs' => 600],
    ];

    [$alignedA] = $this->align('dateKey', [], $a, $b);

    $this->assertEquals(999, $alignedA[0]['f1_wip']);
    $this->assertEquals(42, $alignedA[0]['f1_lots']);
  }
}

<?php

use PHPUnit\Framework\TestCase;
use App\Traits\Sanitize;

class SanitizeTest extends TestCase
{
    use Sanitize;

    public function test_it_returns_null_for_null()
    {
        $this->assertNull($this->sanitizeExcelCell(null));
    }

    public function test_it_returns_null_for_empty_string()
    {
        $this->assertNull($this->sanitizeExcelCell(''));
    }

    public function test_it_returns_null_for_normal_space_only()
    {
        $this->assertNull($this->sanitizeExcelCell('   '));
    }

    public function test_it_returns_null_for_non_breaking_space()
    {
        $this->assertNull($this->sanitizeExcelCell("\u{00A0}"));
        $this->assertNull($this->sanitizeExcelCell("\xC2\xA0"));
        $this->assertNull($this->sanitizeExcelCell("\u{00A0}\u{00A0}"));
    }

    public function test_it_returns_null_for_zero_width_characters()
    {
        $this->assertNull($this->sanitizeExcelCell("\u{200B}")); // ZWSP
        $this->assertNull($this->sanitizeExcelCell("\u{200C}")); // ZWNJ
        $this->assertNull($this->sanitizeExcelCell("\u{200D}")); // ZWJ
        $this->assertNull($this->sanitizeExcelCell("\u{2060}")); // Word joiner
        $this->assertNull($this->sanitizeExcelCell("\u{FEFF}")); // BOM
    }

    public function test_it_returns_null_for_mixed_invisible_whitespace()
    {
        $value = " \u{00A0}\u{200B}\n\t ";
        $this->assertNull($this->sanitizeExcelCell($value));
    }

    public function test_it_normalizes_whitespace_inside_text()
    {
        $this->assertSame(
            'Hello world',
            $this->sanitizeExcelCell("Hello\u{00A0}   world")
        );
    }

    public function test_it_preserves_numeric_strings()
    {
        $this->assertSame('0', $this->sanitizeExcelCell('0'));
        $this->assertSame('123', $this->sanitizeExcelCell('123'));
    }

    public function test_it_preserves_dates_as_strings()
    {
        $this->assertSame(
            '2024-01-15',
            $this->sanitizeExcelCell(" 2024-01-15 ")
        );
    }

    public function test_it_returns_null_for_common_excel_junk_strings()
    {
        $junk = ['N/A', 'NA', 'NULL', 'null', '--', '-', '—', '.', '?'];

        foreach ($junk as $value) {
            $this->assertNull(
                $this->sanitizeExcelCell($value),
                "Failed asserting [$value] becomes null"
            );
        }
    }

    public function test_it_does_not_modify_non_string_values()
    {
        $this->assertSame(123, $this->sanitizeExcelCell(123));
        $this->assertSame(12.5, $this->sanitizeExcelCell(12.5));
        $this->assertSame(false, $this->sanitizeExcelCell(false));
    }
}

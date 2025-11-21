<?php

namespace App\Helpers;

class WipTrendParser
{
  /**
   * Parse and merge trend data from multiple factories into a unified, date-keyed series.
   *
   * This method accepts an associative array of trend series (typically keyed per factory,
   * e.g. "some_factory_trend") and normalizes/merges them into a single array of entries
   * indexed by a computed dateKey. Each entry contains a human-readable label and aggregated
   * total counts for each factory (prefixed by the factory key with "_trend" removed).
   *
   * Behavior and conventions:
   * - Input shape:
   *     [
   *       "<factory_key>_trend" => [
   *         (object|array) [ 'day' => 'YYYY-MM-DD', 'total_quantity' => int, 'total_lots' => int, ... ],
   *         (object|array) [ 'week' => int, 'year' => int, 'total_quantity' => int, ... ],
   *         (object|array) [ 'month' => int, 'year' => int, ... ],
   *         (object|array) [ 'quarter' => int, 'year' => int, ... ],
   *         (object|array) [ 'year' => int, ... ],
   *       ],
   *       ...
   *     ]
   *
   * - Date key and label rules (checked in order: day, week, month, quarter, year):
   *     - day:   uses the original 'day' string as dateKey (expected "YYYY-MM-DD"); label: DateTime->format('M j') e.g. "Jan 3".
   *     - week:  dateKey = "{year}-W{week}" (week is not zero-padded); label: "Week {week}, {year}".
   *     - month: dateKey = DateTime::createFromFormat('Y-n', "{year}-{month}")->format('Y-m') (month is zero-padded); label: DateTime->format('M Y') e.g. "Jan 2020".
   *     - quarter: dateKey = "{year}-Q{quarter}"; label: "Q{quarter} {year}".
   *     - year:  dateKey = "{year}"; label = "{year}".
   *   If none of these keys exist on an item, an empty dateKey/label may be produced.
   *
   * - Factory prefixing:
   *     - Each input top-level key has "_trend" stripped to form a prefix.
   *     - For every item, two fields are added/merged into the date entry:
   *         "{prefix}_total_quantity" => (int) total_quantity or 0 if missing
   *         "{prefix}_total_lots"     => (int) total_lots or 0 if missing
   *
   * - Merging semantics:
   *     - Items that resolve to the same dateKey are merged into a single associative entry.
   *     - A base entry for a dateKey includes 'dateKey' and 'label' (from the first occurrence);
   *       subsequent merges add per-factory total fields and do not replace the existing label.
   *     - Totals default to 0 when absent in an item.
   *
   * - Sorting and return:
   *     - The merged results are sorted ascending by dateKey. The implementation attempts to
   *       interpret dateKey via strtotime(); if that fails for both entries, a string
   *       comparison is used as a fallback.
   *     - The final return value is a numerically indexed array (reindexed via array_values).
   *
   * - Return type:
   *     - array of associative arrays, each containing at minimum:
   *         'dateKey' => string,
   *         'label'   => string,
   *       plus per-factory "{prefix}_total_quantity" and "{prefix}_total_lots" integer fields.
   *
   * Edge cases and notes:
   * - Items are cast to array, so both stdClass objects and arrays are accepted.
   * - Date parsing functions (new DateTime, createFromFormat) may throw exceptions for invalid
   *   date inputs; such exceptions are not caught by this method.
   * - Week keys use a simple "{year}-W{week}" textual form and are not converted to an ISO week date.
   *
   * @param array $trends Associative array of trend series to parse and merge.
   * @return array Numerically indexed, date-sorted array of merged trend entries with labels and per-factory totals.
   */
  public static function parseTrendsByPeriod(array $trends): array
  {
    $merged = [];

    foreach ($trends as $factoryKey => $data) {
      $keyPrefix = str_replace('_trend', '', $factoryKey);

      foreach ($data as $item) {
        $item = (array) $item;

        $dateKey = '';
        $readableLabel = '';

        if (isset($item['day'])) {
          $date = new \DateTime($item['day']);
          $readableLabel = $date->format('M j');
          $dateKey = $item['day'];
        } elseif (isset($item['week'])) {
          $readableLabel = "Week {$item['week']}, {$item['year']}";
          $dateKey = "{$item['year']}-W{$item['week']}";
        } elseif (isset($item['month'])) {
          $date = \DateTime::createFromFormat('Y-n', "{$item['year']}-{$item['month']}");
          $readableLabel = $date->format('M Y');
          $dateKey = $date->format('Y-m');
        } elseif (isset($item['quarter'])) {
          $readableLabel = "Q{$item['quarter']} {$item['year']}";
          $dateKey = "{$item['year']}-Q{$item['quarter']}";
        } elseif (isset($item['year'])) {
          $readableLabel = (string) $item['year'];
          $dateKey = (string) $item['year'];
        }

        $metrics = [];
        foreach ($item as $field => $value) {
          if (in_array($field, ['day', 'week', 'month', 'quarter', 'year'])) {
            continue;
          }

          if (is_numeric($value)) {
            $metrics["{$keyPrefix}_{$field}"] = $value;
          }
        }

        $merged[$dateKey] = array_merge(
          $merged[$dateKey] ?? ['dateKey' => $dateKey, 'label' => $readableLabel],
          $metrics
        );
      }
    }

    // Sort by dateKey (try date first, fallback to string)
    usort($merged, function ($a, $b) {
      $dateA = strtotime($a['dateKey']);
      $dateB = strtotime($b['dateKey']);
      if ($dateA && $dateB) {
        return $dateA <=> $dateB;
      }
      return strcmp($a['dateKey'], $b['dateKey']);
    });

    return array_values($merged);
  }
}

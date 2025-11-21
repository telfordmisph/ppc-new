<?php

namespace App\Helpers;

class MergeAndAggregate
{
  /**
   * Merge multiple datasets and aggregate values by one or more grouping keys.
   *
   * @param array $datasets       One or more arrays or collections of associative arrays or stdClass objects.
   * @param array|string $groupBy Field name(s) to group by.
   * @param array $ignore         Field names to skip during summation.
   * @return array                Aggregated result.
   */
  public static function mergeAndAggregate(array $datasets, array|string $groupBy, array $ignore = []): array
  {
    if (is_string($groupBy)) {
      $groupBy = [$groupBy];
    }

    $merged = [];

    // Normalize: flatten and convert any collections or objects to arrays
    $allItems = [];
    foreach ($datasets as $dataset) {
      if ($dataset instanceof \Illuminate\Support\Collection) {
        $dataset = $dataset->all();
      }

      foreach ($dataset as $item) {
        if (is_object($item)) {
          $allItems[] = (array) $item;
        } else {
          $allItems[] = $item;
        }
      }
    }

    foreach ($allItems as $item) {
      // Compute composite key (like "PL6|2025-10-22")
      $keyParts = [];
      foreach ($groupBy as $field) {
        $keyParts[] = $item[$field] ?? '';
      }
      $key = implode('|', $keyParts);

      // Initialize or update the group
      if (!isset($merged[$key])) {
        $merged[$key] = $item;
      } else {
        foreach ($item as $field => $value) {
          // Skip grouping or ignored fields
          if (in_array($field, $groupBy, true) || in_array($field, $ignore, true)) {
            continue;
          }

          // Sum numeric fields
          if (isset($merged[$key][$field]) && is_numeric($merged[$key][$field]) && is_numeric($value)) {
            $merged[$key][$field] += $value;
          } else {
            $merged[$key][$field] = $value;
          }
        }
      }
    }

    return array_values($merged);
  }
}

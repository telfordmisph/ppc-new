<?php

namespace App\Traits;

trait AlignByKeyTrait
{
  /**
   * Align multiple arrays so they all share the same set of keys.
   * Missing entries are filled with just ['keyField' => value].
   *
   * @param string $keyField  The field to align on (e.g. 'dateKey')
   * @param array  $inheritFields Fields to inherit from the first array
   * @param array  ...$arrays One or more arrays of associative arrays / stdClass objects
   * @return array            Same number of arrays, all with identical key sequences
   */
  protected static function align(string $keyField, array $inheritFields = [], array ...$arrays): array
  {
    $normalized = array_map(function ($arr) {
      return array_map(fn($item) => (array) $item, $arr);
    }, $arrays);

    $allKeys = collect(array_merge(...$normalized))
      ->pluck($keyField)
      ->unique()
      ->sort()
      ->values();

    $inheritMap = [];
    foreach ($normalized as $arr) {
      foreach ($arr as $item) {
        $key = $item[$keyField] ?? null;
        if ($key === null) continue;

        foreach ($inheritFields as $field) {
          if (!isset($inheritMap[$key][$field]) && isset($item[$field])) {
            $inheritMap[$key][$field] = $item[$field];
          }
        }
      }
    }

    return collect($normalized)->map(function ($arr) use ($keyField, $allKeys, $inheritMap) {
      $indexed = collect($arr)->keyBy($keyField);

      return $allKeys->map(function ($key) use ($keyField, $indexed, $inheritMap) {
        if ($indexed->has($key)) {
          return $indexed->get($key);
        }

        return array_merge(
          [$keyField => $key],
          $inheritMap[$key] ?? []
        );
      })->values()->all();
    })->all();
  }
}

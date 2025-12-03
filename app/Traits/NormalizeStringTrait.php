<?php

namespace App\Traits;

trait NormalizeStringTrait
{

  /**
   * Apply normalized alias matching to raw.dimension
   *
   * @param \Illuminate\Database\Query\Builder $query
   * @param array|\Illuminate\Support\Collection $aliases
   * @param string $column
   * @return \Illuminate\Database\Query\Builder
   */
  function applyNormalizedDimensionFilter($query, $aliases, $column)
  {
    $aliasesArray = $aliases instanceof \Illuminate\Support\Collection ? $aliases->toArray() : $aliases;

    // Normalize: lowercase, remove spaces/underscores, trim trailing 's'
    $normalizedAliases = array_map(function ($alias) {
      return rtrim(strtolower(str_replace([' ', '_'], '', $alias)), 's');
    }, $aliasesArray);

    return $query->orWhere(function ($q) use ($normalizedAliases, $column) {
      foreach ($normalizedAliases as $alias) {
        $q->orWhereRaw(
          "REPLACE(REPLACE(LOWER($column), ' ', ''), '_', '') LIKE ?",
          [$alias . '%']
        );
      }
    });
  }

  function uniqueNormalized(array $values)
  {
    $seen = [];
    $result = [];

    foreach ($values as $value) {
      $normalized = strtolower(str_replace([' ', '_'], '', $value));

      if (!isset($seen[$normalized])) {
        $seen[$normalized] = true;
        $result[] = $value;
      }
    }

    return $result;
  }
}

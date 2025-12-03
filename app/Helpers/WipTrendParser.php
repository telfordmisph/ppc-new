<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;


class WipTrendParser
{
  public static function parseTrendsByPeriod(
    array $trends,
    array $prefixMap = [
      'f1_trend' => 'f1',
      'f2_trend' => 'f2',
      'f3_trend' => 'f3',
      'overall_trend' => 'overall'
    ], // common usecase
    array $periodFields = ['day', 'week', 'month', 'quarter', 'year'],
    ?callable $metricFilter = null
  ): array {
    $metricFilter = $metricFilter ?? fn($k, $v) => is_numeric($v);
    $merged = [];

    foreach ($trends as $factoryKey => $data) {
      $keyPrefix = $prefixMap[$factoryKey] ?? $factoryKey;

      foreach ($data as $item) {
        $item = (array) $item;

        $dateKey = '';
        $readableLabel = '';

        foreach ($periodFields as $period) {
          if (!isset($item[$period])) continue;

          switch ($period) {
            case 'day':
              $date = new \DateTime($item['day']);
              $readableLabel = $date->format('M j');
              $dateKey = $item['day'];
              break;
            case 'week':
              $readableLabel = "Week {$item['week']}, {$item['year']}";
              $dateKey = "{$item['year']}-W{$item['week']}";
              break;
            case 'month':
              $date = \DateTime::createFromFormat('Y-n', "{$item['year']}-{$item['month']}");
              $readableLabel = $date->format('M Y');
              $dateKey = $date->format('Y-m');
              break;
            case 'quarter':
              $readableLabel = "Q{$item['quarter']} {$item['year']}";
              $dateKey = "{$item['year']}-Q{$item['quarter']}";
              break;
            case 'year':
              $readableLabel = (string) $item['year'];
              $dateKey = (string) $item['year'];
              break;
          }

          break; // stop after first matching period
        }

        $metrics = [];
        foreach ($item as $field => $value) {
          if (in_array($field, $periodFields)) continue;
          if ($metricFilter($field, $value)) {
            $metrics["{$keyPrefix}_{$field}"] = $value;
          }
        }

        $merged[$dateKey] = array_merge(
          $merged[$dateKey] ?? ['dateKey' => $dateKey, 'label' => $readableLabel],
          $metrics
        );
      }
    }

    usort($merged, function ($a, $b) {
      $dateA = strtotime($a['dateKey']);
      $dateB = strtotime($b['dateKey']);
      if ($dateA && $dateB) return $dateA <=> $dateB;
      return strcmp($a['dateKey'], $b['dateKey']);
    });

    return array_values($merged);
  }


  // public static function parseTrendsByPeriod(array $trends): array
  // {
  //   $merged = [];

  //   foreach ($trends as $factoryKey => $data) {
  //     $keyPrefix = str_replace('_trend', '', $factoryKey);

  //     foreach ($data as $item) {
  //       $item = (array) $item;

  //       $dateKey = '';
  //       $readableLabel = '';

  //       if (isset($item['day'])) {
  //         $date = new \DateTime($item['day']);
  //         $readableLabel = $date->format('M j');
  //         $dateKey = $item['day'];
  //       } elseif (isset($item['week'])) {
  //         $readableLabel = "Week {$item['week']}, {$item['year']}";
  //         $dateKey = "{$item['year']}-W{$item['week']}";
  //       } elseif (isset($item['month'])) {
  //         $date = \DateTime::createFromFormat('Y-n', "{$item['year']}-{$item['month']}");
  //         $readableLabel = $date->format('M Y');
  //         $dateKey = $date->format('Y-m');
  //       } elseif (isset($item['quarter'])) {
  //         $readableLabel = "Q{$item['quarter']} {$item['year']}";
  //         $dateKey = "{$item['year']}-Q{$item['quarter']}";
  //       } elseif (isset($item['year'])) {
  //         $readableLabel = (string) $item['year'];
  //         $dateKey = (string) $item['year'];
  //       }

  //       $metrics = [];
  //       foreach ($item as $field => $value) {
  //         if (in_array($field, ['day', 'week', 'month', 'quarter', 'year'])) {
  //           continue;
  //         }

  //         if (is_numeric($value)) {
  //           $metrics["{$keyPrefix}_{$field}"] = $value;
  //         }
  //       }

  //       $merged[$dateKey] = array_merge(
  //         $merged[$dateKey] ?? ['dateKey' => $dateKey, 'label' => $readableLabel],
  //         $metrics
  //       );
  //     }
  //   }

  //   // Sort by dateKey (try date first, fallback to string)
  //   usort($merged, function ($a, $b) {
  //     $dateA = strtotime($a['dateKey']);
  //     $dateB = strtotime($b['dateKey']);
  //     if ($dateA && $dateB) {
  //       return $dateA <=> $dateB;
  //     }
  //     return strcmp($a['dateKey'], $b['dateKey']);
  //   });

  //   return array_values($merged);
  // }
}

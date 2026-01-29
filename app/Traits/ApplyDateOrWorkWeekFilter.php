<?php

namespace App\Traits;

trait ApplyDateOrWorkWeekFilter
{
  protected function applyDateOrWorkweekWipFilter($query, $dateColumn, $useWorkweek, $workweek, $startDate = null, $endDate = null)
  {
    if ($useWorkweek) {
      $workweekArray = array_map('intval', explode(' ', $workweek));
      $weekRanges = $this->analogCalendarRepo->getWorkweekRanges($workweekArray);

      // Log::info("Workweek: " . print_r($workweekArray, true));
      // Log::info("Week Ranges: " . print_r($weekRanges, return: true));

      return $query->where(function ($q) use ($dateColumn, $weekRanges) {
        foreach ($weekRanges as $range) {
          $q->orWhere(function ($q2) use ($dateColumn, $range) {
            $q2->where($dateColumn, '>=', $range->start_date)
              ->where($dateColumn, '<', $range->end_date);
          });
        }
      });
    }

    return $query->where(function ($q) use ($dateColumn, $startDate, $endDate) {
      $q->where($dateColumn, '>=', $startDate)
        ->where($dateColumn, '<', $endDate);
    });
  }

  protected function applyDateOrWorkweekOutFilter($query, $dateColumn, $useWorkweek, $weekRange, $startDate = null, $endDate = null)
  {
    if ($useWorkweek) {
      return $query->where(function ($q) use ($dateColumn, $weekRange) {
        foreach ($weekRange as $range) {
          $q->orWhere(function ($q2) use ($dateColumn, $range) {
            $q2->where($dateColumn, '>=', $range->startDate)
              ->where($dateColumn, '<', $range->endDate);
          });
        }
      });
    }

    return $query->where(function ($q) use ($dateColumn, $startDate, $endDate) {
      $q->where($dateColumn, '>=', $startDate)
        ->where($dateColumn, '<', $endDate);
    });
  }
}

<?php

namespace App\Repositories;

use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Facades\DB;

class AnalogCalendarRepository
{
  private const ANALOG_CALENDAR_TABLE = "analog_calendar";

  private const TABLE = "analog_calendar";

  public function getWorkweekRanges(array $workweekArray)
  {
    return DB::table(self::TABLE)
      ->selectRaw('MIN(cal_date) AS start_date, MAX(cal_date) AS end_date')
      ->whereIn('cal_workweek', $workweekArray)
      ->groupBy('cal_workweek')
      ->get();
  }

  /*
   * @param $workweeks
   * @return array
  */
  public function getDatesByWorkWeekRange($workweeks)
  {
    if (!is_array($workweeks)) {
      $trimmed = trim($workweeks);

      if ($trimmed === '') {
        $workweeks = [];
      } else {
        $workweeks = array_map('intval', preg_split('/[,\s]+/', $trimmed));
      }
    }

    $ranges = [];

    foreach ($workweeks as $ww) {
      $query = DB::table(self::ANALOG_CALENDAR_TABLE)
        ->where('cal_workweek', $ww)
        ->whereNotNull('cal_date')
        ->orderBy('cal_workweek', 'asc')
        ->orderBy('cal_date', 'asc');

      $startDate = $query->min('cal_date');
      $endDate = $query->max('cal_date');

      if ($startDate && $endDate) {
        $ranges[] = (object)[
          'workweek' => $ww,
          'startDate' => $startDate,
          'endDate' => $endDate,
        ];
      }
    }

    if (empty($ranges)) {
      return [
        'range' => [],
        'earliest_date' => null,
        'latest_date' => null,
      ];
    }

    $earliest = null;
    $latest = null;
    foreach ($ranges as $r) {
      if ($earliest === null || $r->startDate < $earliest) {
        $earliest = $r->startDate;
      }
      if ($latest === null || $r->endDate > $latest) {
        $latest = $r->endDate;
      }
    }

    return [
      'range' => $ranges,
      'earliest_date' => $earliest,
      'latest_date' => $latest,
    ];
  }
}

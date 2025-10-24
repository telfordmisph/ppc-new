<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class AnalogCalendarRepository
{
  private const TABLE = "analog_calendar";

  public function getWorkweekRanges(array $workweekArray)
  {
    return DB::table(self::TABLE)
      ->selectRaw('MIN(cal_date) AS start_date, MAX(cal_date) AS end_date')
      ->whereIn('cal_workweek', $workweekArray)
      ->groupBy('cal_workweek')
      ->get();
  }
}

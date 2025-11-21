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

  public function getDatesByWorkWeekRange($workweeks)
  {
    \Log::info('workweek: ', ['data' => $workweeks]);

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
        ->whereNotNull('cal_date');

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

    return $ranges;
  }
}

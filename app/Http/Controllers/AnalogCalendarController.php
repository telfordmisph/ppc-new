<?php

namespace App\Http\Controllers;

use App\Repositories\AnalogCalendarRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalogCalendarController extends Controller
{
  private const WORKWEEK_CACHE_KEY = 'workweek';
  private const CACHE_HOURS = 26;
  private const ANALOG_CALENDAR_TABLE = "analog_calendar";

  private $analogCalendarRepository;

  public function __construct(AnalogCalendarRepository $analogCalendarRepository)
  {
    $this->analogCalendarRepository = $analogCalendarRepository;
  }

  // public function getWorkWeek()
  // {
  //   $workweeks = Cache::remember(self::WORKWEEK_CACHE_KEY, now()->addHours(self::CACHE_HOURS), function () {
  //     return DB::table(self::ANALOG_CALENDAR_TABLE)
  //       ->whereNotNull('cal_workweek')
  //       ->groupBy('cal_workweek')
  //       ->select(
  //         'cal_workweek',
  //         DB::raw('MIN(cal_date) as startDate'),
  //         DB::raw('MAX(cal_date) as endDate')
  //       )
  //       ->orderBy('cal_workweek', 'desc')
  //       ->get();
  //   });

  //   return response()->json([
  //     'data' => $workweeks,
  //     'status' => 'success',
  //     'message' => 'Data retrieved successfully',
  //   ]);
  // }

  public function getWorkWeek()
  {
    $workweeks = DB::table(self::ANALOG_CALENDAR_TABLE)
      ->whereNotNull('cal_workweek')
      ->groupBy('cal_workweek')
      ->select(
        'cal_workweek',
        DB::raw('MIN(cal_date) as startDate'),
        DB::raw('MAX(cal_date) as endDate')
      )
      ->orderBy('cal_workweek', 'desc')
      ->get();

    return response()->json([
      'data' => $workweeks,
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]);
  }


  public function getDatesByWorkWeekRange($workweek)
  {
    \Log::info("Fetching dates by workweek ...");

    $result = $this->analogCalendarRepository->getDatesByWorkWeekRange($workweek);

    return response()->json([
      'startDate' => $result['startDate'],
      'endDate' => $result['endDate'],
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]);
  }
}

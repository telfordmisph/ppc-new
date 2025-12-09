<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Repositories\PackageCapacityRepository;
use App\Repositories\AnalogCalendarRepository;
use Carbon\Carbon;

class PackageCapacityService
{
  protected $packageCapacityRepository;
  protected $analogCalendarRepo;

  public function __construct(
    PackageCapacityRepository $packageCapacityRepository,
    AnalogCalendarRepository $analogCalendarRepo
  ) {
    $this->packageCapacityRepository = $packageCapacityRepository;
    $this->analogCalendarRepo = $analogCalendarRepo;
  }

  public function getLatestPackageCapacity($package, $factory)
  {
    return $this->packageCapacityRepository->getLatestPackageCapacity($package, $factory);
  }

  public function upsertMultiple($data)
  {
    return $this->packageCapacityRepository->upsertMultiple($data);
  }

  public function insert($latest, $packageCapacity)
  {
    return $this->packageCapacityRepository->upsert($latest, $packageCapacity);
  }

  public function update($latest, $id, $packageCapacity)
  {
    return $this->packageCapacityRepository->update($latest, $id, $packageCapacity);
  }

  public function get($id)
  {
    return $this->packageCapacityRepository->get($id);
  }

  public function rules($package_name, $factory_name, $effective_from, $id = null, $latest = null)
  {

    $uniqueRule = 'unique:package_capacity_history,package_name,' . ($id ?? 'NULL')
      . ',id,factory_name,' . $factory_name
      . ',effective_from,' . $effective_from;

    $rules = [
      'package_name'   => ['required', 'string', 'max:45', $uniqueRule],
      'factory_name'   => 'required|string|max:20',
      'capacity'       => 'nullable|integer|min:0',
      'effective_from' => ['required', 'date'],
      'effective_to'   => 'nullable|date|after_or_equal:effective_from',
    ];

    if ($latest) {
      $rules['effective_from'][] = function ($attribute, $value, $fail) use ($latest) {
        if ($value <= $latest->effective_from) {
          $fail("The effective_from date must be after the latest capacity date ({$latest->effective_from}).");
        }
      };
    }

    return $rules;
  }

  private function aggregateWeekly(array $daily, array $weekRanges): array
  {
    $result = [];

    foreach ($daily as $item) {
      if (empty($item['day']) || !isset($item['capacity'])) continue;

      $date = Carbon::parse($item['day']);

      foreach ($weekRanges as $range) {
        // Log::info('Range: ' . json_encode($range));

        $start = Carbon::parse($range->startDate);
        $end = Carbon::parse($range->endDate);

        if ($date->between($start, $end)) {
          $year = $date->year;
          $week = $range->workweek;
          $key = $year . '-' . $week;

          if (!isset($result[$key])) {
            $result[$key] = [
              'year' => $year,
              'workweek' => 'w' . $week,
              'week' => $week,
              'capacity' => 0,
            ];
          }

          $result[$key]['capacity'] += $item['capacity'];
          break; // found the correct week range
        }
      }
    }

    // Log::info('Result: ' . json_encode($result));

    return array_values($result);
  }

  private function aggregateDaily(array $rows): array
  {
    $result = [];

    foreach ($rows as $item) {
      if (empty($item['day']) || !isset($item['capacity'])) continue;

      $date = Carbon::parse($item['day'])->toDateString(); // normalize to YYYY-MM-DD

      if (!isset($result[$date])) {
        $result[$date] = [
          'day' => $date,
          'capacity' => 0,
        ];
      }

      $result[$date]['capacity'] += $item['capacity'];
    }

    return array_values($result);
  }

  private function aggregateMonthly(array $daily): array
  {
    $result = [];

    foreach ($daily as $item) {
      if (empty($item['day']) || !isset($item['capacity'])) continue;

      $date = Carbon::parse($item['day']);
      $year = $date->year;
      $monthName = $date->format('n');

      $key = $year . '-' . $monthName;

      if (!isset($result[$key])) {
        $result[$key] = [
          'year' => $year,
          'month' => $monthName,
          'capacity' => 0,
        ];
      }

      $result[$key]['capacity'] += $item['capacity'];
    }

    return array_values($result);
  }

  private function aggregateQuarterly(array $daily): array
  {
    $result = [];

    foreach ($daily as $item) {
      if (empty($item['day']) || !isset($item['capacity'])) continue;

      $date = Carbon::parse($item['day']);
      $year = $date->year;
      $quarter = $date->quarter;

      $key = $year . '-Q' . $quarter;

      if (!isset($result[$key])) {
        $result[$key] = [
          'year' => $year,
          'quarter' => $quarter,
          'capacity' => 0,
        ];
      }

      $result[$key]['capacity'] += $item['capacity'];
    }

    return array_values($result);
  }

  private function aggregateYearly(array $daily): array
  {
    $result = [];

    foreach ($daily as $item) {
      if (empty($item['day']) || !isset($item['capacity'])) continue;

      $date = Carbon::parse($item['day']);
      $year = $date->year;

      if (!isset($result[$year])) {
        $result[$year] = [
          'year' => $year,
          'capacity' => 0,
        ];
      }

      $result[$year]['capacity'] += $item['capacity'];
    }

    return array_values($result);
  }


  public function getSummaryLatestAndPrevious($factory)
  {
    return $this->packageCapacityRepository->getSummaryLatestAndPrevious($factory);
  }

  public function getCapacityTrend($package, $factory, $period, $startDate, $endDate, $workweeks)
  {
    // Log::info("Getting Capacity Trend for Package: $package, Factory: $factory, Period: $period, Start Date: $startDate, End Date: $endDate, Workweeks: $workweeks");
    $earliestStartDate = $startDate;

    $weekRanges = null;
    if ($period == 'weekly') {
      $weekRanges = $this->analogCalendarRepo->getDatesByWorkWeekRange($workweeks);

      // Log::info("Week Rangessss: " . json_encode($weekRanges));

      $earliestStartDate = $weekRanges['earliest_date'];
      $endDate = $weekRanges['latest_date'];
    }

    $rows = $this->packageCapacityRepository->getPackageCapacity($package, $factory);
    $daily = $this->packageCapacityRepository->expandDaily($rows, $earliestStartDate, $endDate);

    $daily = array_filter($daily, function ($d) use ($earliestStartDate) {
      return Carbon::parse($d['day'])->gt($earliestStartDate);
    });

    // TODO DFN SSOP
    // Showing 549, 601, 552, 551, 550, and 602 workweeks
    // 340k result on capacity? it might consider the rest day
    // make sure to use the calendar of analog ???
    // the WIP is continuous 7 days a week, so it's fine

    // Log::info("packages: " . json_encode($package));
    // Log::info("Period: " . $period);
    // Log::info("Start Date: " . $startDate);
    // Log::info("End Date: " . $endDate);
    // Log::info("Factory: " . $factory);
    // Log::info("Period: " . $period);
    // Log::info("Week Ranges: " . json_encode($weekRanges));

    if ($period == 'weekly') {
      $daily = $this->aggregateWeekly($daily, $weekRanges['range']);
    } elseif ($period == 'monthly') {
      $daily = $this->aggregateMonthly($daily);
    } elseif ($period == 'quarterly') {
      $daily = $this->aggregateQuarterly($daily);
    } elseif ($period == 'yearly') {
      $daily = $this->aggregateYearly($daily);
    } elseif ($period == 'daily') {
      $daily = $this->aggregateDaily($daily);
    }

    // Log::info("Aggregated: " . json_encode($daily));

    return $daily;
  }
}

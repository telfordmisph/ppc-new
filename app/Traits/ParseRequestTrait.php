<?php


namespace App\Traits;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Traits\ParseDateTrait;


trait ParseRequestTrait
{
  use ParseDateTrait;

  protected function parsePeriodParams(Request $request, int $defaultLookBack = 20): array
  {
    $period = $request->input('period', 'daily');
    $offsetDays = (int) $request->input('offsetDays', 0);
    $lookBack = (int) $request->input('lookBack', $defaultLookBack);

    $endDate = Carbon::now()
      ->subDays($offsetDays)
      ->startOfDay();

    $startDate = null;

    if ($period === 'daily') {
      $dateRange = $this->parseDateRange($request->input('dateRange', ''));

      if ($dateRange) {
        $startDate = $dateRange['start'];
        $endDateExclusive = $dateRange['end'];
      } else {
        $startDate = (clone $endDate)->subDays($lookBack)->startOfDay();
        $endDateExclusive = (clone $endDate)->addDay();
      }
    } else {
      switch ($period) {
        case 'monthly':
          $startDate = (clone $endDate)->subMonths($lookBack)->startOfMonth();
          break;

        case 'quarterly':
          $startDate = (clone $endDate)->subQuarters($lookBack)->startOfQuarter();
          break;

        case 'yearly':
          $startDate = (clone $endDate)->subYears($lookBack)->startOfYear();
          break;

        default:
          $startDate = (clone $endDate)->subDays($lookBack)->startOfDay();
          break;
      }

      $endDateExclusive = (clone $endDate)->addDay();
    }

    return [
      'period' => $period,
      'lookBack' => $lookBack,
      'offsetDays' => $offsetDays,
      'startDate' => $startDate,
      'endDate' => $endDateExclusive,
    ];
  }


  protected function parseWorkweek(Request $request): array
  {
    $workweek = $request->input('workweek', '') ?? '';
    $period = $request->input('period', '');

    return [
      'workweek' => $period === 'weekly' ? $workweek : '',
      'useWorkweek' => $period === 'weekly',
    ];
  }

  protected function parsePackageName(Request $request, string $inputName = 'packageName'): array
  {
    $input = $request->input($inputName, '') ?? '';
    $packages = explode(',', $input);
    return array_filter($packages, fn($p) => !empty($p));
  }
}

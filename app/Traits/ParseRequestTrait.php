<?php


namespace App\Traits;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

trait ParseRequestTrait
{
  protected function parsePeriodParams(Request $request, int $defaultLookBack = 20): array
  {
    $period = $request->input('period', 'daily') ?? 'daily';
    $offsetDays = $request->input('offsetDays', 0) ?? 0;
    $lookBack = $request->input('lookBack', $defaultLookBack) ?? $defaultLookBack;
    $endDate = Carbon::now()->subDays($offsetDays)->endOfDay();
    $startDate = null;

    switch ($period) {
      case 'daily':
        $startDate = (clone $endDate)->subDays($lookBack)->startOfDay();
        break;
      case 'monthly':
        $startDate = (clone $endDate)->subMonths($lookBack)->startOfMonth();
        break;
      case 'quarterly':
        $startDate = (clone $endDate)->subQuarters($lookBack)->startOfQuarter();
        break;
      case 'yearly':
        $startDate = (clone $endDate)->subYears($lookBack)->startOfYear();
        break;
    }

    return [
      'period' => $period,
      'lookBack' => $lookBack,
      'offsetDays' => $offsetDays,
      'startDate' => $startDate,
      'endDate' => $endDate
    ];
  }

  protected function parseWorkweek(Request $request): array
  {
    $workweek = $request->input('workweek', '') ?? '';

    return [
      'workweek' => $workweek,
      'useWorkweek' => !empty($workweek),
    ];
  }

  protected function parsePackageName(Request $request, string $inputName = 'packageName'): array
  {
    $input = $request->input($inputName, '') ?? '';
    $packages = explode(',', $input);
    return array_filter($packages, fn($p) => !empty($p));
  }
}

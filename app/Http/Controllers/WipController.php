<?php

namespace App\Http\Controllers;

use App\Traits\ParseDateTrait;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\WipService;
use App\Repositories\F1F2WipRepository;
use Illuminate\Support\Facades\Log;

class WipController extends Controller
{
  use ParseDateTrait;

  protected $wipService;
  protected $wipRepository;

  private const CATEGORY_FILTER = ['all', 'F1', 'F2', 'F3', 'PL1', 'PL6'];

  public function __construct(WipService $wipService, F1F2WipRepository $wipRepository)
  {
    $this->wipService = $wipService;
    $this->wipRepository = $wipRepository;
  }

  // ------------------------
  // Helper Methods
  // ------------------------
  private function parsePackageName(Request $request, string $inputName = 'packageName'): array
  {
    $input = $request->input($inputName, '') ?? '';
    $packages = explode(',', $input);
    return array_filter($packages, fn($p) => !empty($p));
  }

  private function parsePeriodParams(Request $request, int $defaultLookBack = 20): array
  {
    return [
      'period' => $request->input('period', 'daily') ?? 'daily',
      'lookBack' => $request->input('lookBack', $defaultLookBack) ?? $defaultLookBack,
      'offsetDays' => $request->input('offsetDays', 0) ?? 0,
    ];
  }

  private function parseWorkweek(Request $request): array
  {
    $workweek = $request->input('workweek', '') ?? '';
    return [
      'workweek' => $workweek,
      'useWorkweek' => !empty($workweek),
    ];
  }

  private function parseDateRangeFromRequest(Request $request): array
  {
    return $this->parseDateRange($request->input('dateRange', '')) ?? '';
  }

  private function validateChartStatus(string $status): bool
  {
    return in_array($status, self::CATEGORY_FILTER);
  }

  // ------------------------
  // API Methods
  // ------------------------
  public function getDistinctPackages()
  {
    Log::info("Fetching getWipOutTrend ...");

    $results = $this->wipRepository->getDistinctPackages();

    return response()->json([
      'data' => $results,
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]);
  }

  public function getWIPStationTrend(Request $request)
  {
    $filteringCondition = trim($request->input('filteringCondition', '') ?? '');
    $packageName = $this->parsePackageName($request);
    $periodParams = $this->parsePeriodParams($request);

    return $this->wipService->getWIPStationSummaryTrend(
      $packageName,
      $periodParams['period'],
      $periodParams['lookBack'],
      $periodParams['offsetDays'],
      $filteringCondition
    );
  }

  public function getTodayWip()
  {
    return $this->wipService->getTodayWip();
  }

  public function getOverallWip(Request $request)
  {
    $workweekParams = $this->parseWorkweek($request);
    $dates = $this->parseDateRangeFromRequest($request);

    return $this->wipService->getOverallWip(
      $dates['start'],
      $dates['end'],
      $workweekParams['useWorkweek'],
      $workweekParams['workweek']
    );
  }

  public function getOverallWipByPackage(Request $request)
  {
    $workweekParams = $this->parseWorkweek($request);
    $packageName = $this->parsePackageName($request);
    $periodParams = $this->parsePeriodParams($request, 3);
    $dates = $this->parseDateRangeFromRequest($request);
    Log::info("Overall WIP By Package Trefasdfasfsdsdfsdfnd: ");
    return $this->wipService->getOverallWipByPackage(
      $dates['start'],
      $dates['end'],
      $workweekParams['useWorkweek'],
      $workweekParams['workweek'],
      $packageName,
      $periodParams['period'],
      $periodParams['lookBack'],
      $periodParams['offsetDays']
    );
  }

  public function getOverallPickUp(Request $request)
  {
    $dates = $this->parseDateRangeFromRequest($request);
    return $this->wipService->getOverallPickUp($dates['start'], $dates['end']);
  }

  public function getOverallResidual(Request $request)
  {
    $dates = $this->parseDateRangeFromRequest($request);
    return $this->wipService->getOverallResidual($dates['start'], $dates['end']);
  }

  public function getPackageResidualSummary(Request $request)
  {
    $dates = $this->parseDateRangeFromRequest($request);
    $chartStatus = $request->input('chartStatus', 'all') ?? 'all';

    if (!$this->validateChartStatus($chartStatus)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid chart status: ' . $chartStatus,
      ], 400);
    }

    return $this->wipService->getPackageResidualSummary($chartStatus, $dates['start'], $dates['end']);
  }

  public function getPackagePickUpSummary(Request $request)
  {
    $dates = $this->parseDateRangeFromRequest($request);
    $chartStatus = $request->input('chartStatus', 'all') ?? 'all';

    if (!$this->validateChartStatus($chartStatus)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Invalid chart status: ' . $chartStatus,
      ], 400);
    }

    return $this->wipService->getPackagePickUpSummary($chartStatus, $dates['start'], $dates['end']);
  }

  public function getPackagePickUpTrend(Request $request)
  {
    $packageName = $this->parsePackageName($request);
    $periodParams = $this->parsePeriodParams($request);

    return $this->wipService->getPackagePickUpTrend(
      $packageName,
      $periodParams['period'],
      $periodParams['lookBack'],
      $periodParams['offsetDays']
    );
  }

  public function getWIPQuantityAndLotsTotal(Request $request)
  {
    $workweekParams = $this->parseWorkweek($request);
    $dates = $this->parseDateRangeFromRequest($request);
    $includePL = $request->input('includePL', true) ?? true;

    return $this->wipService->getWIPQuantityAndLotsTotal(
      $workweekParams['useWorkweek'],
      $workweekParams['workweek'],
      $dates['start'],
      $dates['end'],
      $includePL
    );
  }

  public function getWIPQuantityAndLotsTotalNew(Request $request)
  {
    $packageName = $this->parsePackageName($request);
    $periodParams = $this->parsePeriodParams($request);
    $workweek = $request->input('workweek', '') ?? '';

    return $this->wipService->getWIPQuantityAndLotsTotalNew(
      $packageName,
      $periodParams['period'],
      $periodParams['lookBack'],
      $periodParams['offsetDays'],
      $workweek
    );
  }

  public function getWipOutTrend(Request $request)
  {

    $packageName = $this->parsePackageName($request);
    $periodParams = $this->parsePeriodParams($request);
    $workweeks = $this->parseWorkweek($request);

    Log::info("Fetching getWipOutTrend ..." . json_encode($workweeks['workweek']));

    return $this->wipService->getWipOutCapacitySummaryTrend(
      $packageName,
      $periodParams['period'],
      $periodParams['lookBack'],
      $periodParams['offsetDays'],
      $workweeks['workweek']
    );
  }

  public function wipStation()
  {
    return Inertia::render('WIPStation');
  }
}

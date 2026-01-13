<?php

namespace App\Http\Controllers;

use App\Constants\WipConstants;
use App\Repositories\PackageGroupRepository;
use App\Traits\ParseDateTrait;
use App\Traits\ParseRequestTrait;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\WipService;
use App\Repositories\F1F2WipRepository;
use Illuminate\Support\Facades\Log;

class WipController extends Controller
{
  use ParseRequestTrait;

  protected $wipService;
  protected $f1f2WipRepo;
  protected $packageGroupRepo;

  private const CATEGORY_FILTER = ['all', 'F1', 'F2', 'F3', 'PL1', 'PL6'];

  public function __construct(
    WipService $wipService,
    F1F2WipRepository $f2f2WipRepo,
    PackageGroupRepository $packageGroupRepo
  ) {
    $this->packageGroupRepo = $packageGroupRepo;
    $this->wipService = $wipService;
    $this->f1f2WipRepo = $f2f2WipRepo;
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

    // TODO: use a separate table, perhaps use the excel file from PPC portal at 'downloads' folder

    $packages = $this->wipService->getAllPackages();
    Log::info("Packages: " . json_encode($packages));
    return response()->json([
      'data' => $packages,
      // 'data' => WipConstants::DISTINCT_PACKAGES,
      // 'data' => ["TSSOP (240 mils)", "150 mils"],
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]);
  }

  public function getWIPStationTrend(Request $request)
  {
    $filteringCondition = trim($request->input('filteringCondition', '') ?? '');
    $packageName = $this->parsePackageName($request);
    $periodParams = $this->parsePeriodParams($request);
    $workweekParams = $this->parseWorkweek($request);

    return $this->wipService->getWIPStationSummaryTrend(
      $packageName,
      $periodParams['period'],
      $periodParams['startDate'],
      $periodParams['endDate'],
      $workweekParams['workweek'],
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
    return $this->wipService->getAllWipTrendByPackage(
      $workweekParams['workweek'],
      $packageName,
      $periodParams['period'],
      $periodParams['startDate'],
      $periodParams['endDate']
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
    $workweekParams = $this->parseWorkweek($request);

    return $this->wipService->getPackagePickUpTrend(
      $packageName,
      $periodParams['period'],
      $periodParams['startDate'],
      $periodParams['endDate'],
      $workweekParams['workweek']
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

  public function getWipOutCapacitySummaryTrend(Request $request)
  {

    $packageName = $this->parsePackageName($request);
    $periodParams = $this->parsePeriodParams($request);
    $workweeks = $this->parseWorkweek($request);

    Log::info("Fetching getWipOutTrend ..." . json_encode($workweeks['workweek']));

    return $this->wipService->getWipOutCapacitySummaryTrend(
      $packageName,
      $periodParams['period'],
      $periodParams['startDate'],
      $periodParams['endDate'],
      $workweeks['workweek']
    );
  }

  public function getWipOutTrendRawData(Request $request)
  {

    $packageName = $this->parsePackageName($request);
    $periodParams = $this->parsePeriodParams($request);
    return $this->wipService->downloadAllFactoriesRawXlsx(
      $packageName,
      $periodParams['startDate'],
      $periodParams['endDate'],
    );
  }

  public function getPickUpTrendRawData(Request $request)
  {

    $packageName = $this->parsePackageName($request);
    $periodParams = $this->parsePeriodParams($request);
    return $this->wipService->downloadPickUpRawXlsx(
      $packageName,
      $periodParams['startDate'],
      $periodParams['endDate'],
    );
  }

  public function wipStation()
  {
    return Inertia::render('WIPStation');
  }
}

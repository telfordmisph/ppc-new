<?php

namespace App\Http\Controllers;

use App\Traits\ParseDateTrait;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\WipService;
use App\Repositories\WipRepository;
use Illuminate\Support\Facades\Log;

class WipController extends Controller
{
  use ParseDateTrait;

  protected $wipService;
  protected $wipRepository;

  public function __construct(WipService $wipService, WipRepository $wipRepository)
  {
    $this->wipService = $wipService;
    $this->wipRepository = $wipRepository;
  }

  private const CATEGORY_FILTER = ['all', 'F1', 'F2', 'F3', 'PL1', 'PL6'];

  public function getDistinctPackages()
  {
    $results = $this->wipRepository->getDistinctPackages();
    Log::info('Distinct Packages: ' . print_r($results, true));

    return response()->json([
      'data' => $results,
      'status' => 'success',
      'message' => 'Data retrieved successfully',
    ]);
  }

  public function getWIPStationTrend(Request $request)
  {
    $filteringCondition = trim($request->input('filteringCondition', ''));
    $packageName = $request->input('packageName', ''); // empty for all
    $period = $request->input('period', 'daily');
    $lookBack = $request->input('lookBack', 20);
    $offsetDays = $request->input('offsetDays', 0);

    return $this->wipService->getWIPFilterSummaryTrend($packageName, $period, $lookBack, $offsetDays, $filteringCondition);
  }

  public function getTodayWip()
  {
    return $this->wipService->getTodayWip();
  }

  public function getOverallWip(Request $request)
  {
    $workweek = $request->input('workweek', '');
    $useWorkweek = !empty($workweek);
    ['start' => $startDate, 'end' => $endDate] = $this->parseDateRange($request->input('dateRange', ''));

    return $this->wipService->getOverallWip($startDate, $endDate, $useWorkweek, $workweek);
  }

  public function getOverallWipByPackage(Request $request)
  {
    $workweek = $request->input('workweek', '');
    $packageName = $request->input('packageName', ''); // empty for all
    $period = $request->input('period', 'daily');
    $lookBack = $request->input('lookBack', 3);
    $offsetDays = $request->input('offsetDays', 0);

    $useWorkweek = !empty($workweek);
    ['start' => $startDate, 'end' => $endDate] = $this->parseDateRange($request->input('dateRange', ''));

    return $this->wipService->getOverallWipByPackage($startDate, $endDate, $useWorkweek, $workweek, $packageName, $period, $lookBack, $offsetDays);
  }

  public function getOverallPickUp(Request $request)
  {
    ['start' => $startDate, 'end' => $endDate] = $this->parseDateRange($request->input('dateRange', ''));

    return $this->wipService->getOverallPickUp($startDate, $endDate);
  }

  public function getOverallResidual(Request $request)
  {
    ['start' => $startDate, 'end' => $endDate] = $this->parseDateRange($request->input('dateRange', ''));

    return $this->wipService->getOverallResidual($startDate, $endDate);
  }

  public function getPackageResidualSummary(Request $request)
  {
    ['start' => $startDate, 'end' => $endDate] = $this->parseDateRange($request->input('dateRange', ''));

    $chartStatus = $request->input('chartStatus', 'all');

    if (!in_array($chartStatus, self::CATEGORY_FILTER)) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid chart status: ' . $chartStatus,
      ], 400);
    }

    return $this->wipService->getPackageResidualSummary($chartStatus, $startDate, $endDate);
  }

  public function getPackagePickUpSummary(Request $request)
  {
    ['start' => $startDate, 'end' => $endDate] = $this->parseDateRange($request->input('dateRange', ''));

    $chartStatus = $request->input('chartStatus', 'all');

    if (!in_array($chartStatus, self::CATEGORY_FILTER)) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Invalid chart status: ' . $chartStatus,
      ], 400);
    }

    return $this->wipService->getPackagePickUpSummary($chartStatus, $startDate, $endDate);
  }

  public function getPackagePickUpTrend(Request $request)
  {
    $packageName = $request->input('packageName', ''); // empty for all
    $period = $request->input('period', 'daily');
    $lookBack = $request->input('lookBack', 20);
    $offsetDays = $request->input('offsetDays', 0);

    return $this->wipService->getPackagePickUpTrend($packageName, $period, $lookBack, $offsetDays);
  }

  public function getWIPQuantityAndLotsTotal(Request $request)
  {
    $workweek  = $request->input('workweek', '');
    $useWorkweek = !empty($workweek);
    ['start' => $startDate, 'end' => $endDate] = $this->parseDateRange($request->input('dateRange', ''));
    $includePL = $request->input('includePL', true);

    return $this->wipService->getWIPQuantityAndLotsTotal($useWorkweek, $workweek, $startDate, $endDate, $includePL);
  }

  public function wipTable()
  {
    return Inertia::render('WIPTable');
  }
}

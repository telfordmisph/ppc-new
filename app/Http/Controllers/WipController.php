<?php

namespace App\Http\Controllers;

use App\Traits\ParseDateTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Services\WipService;
use Illuminate\Database\Query\Builder;
use Exception;

class WipController extends Controller
{
  use ParseDateTrait;

  protected $wipService;

  public function __construct(WipService $wipService)
  {
    $this->wipService = $wipService;
  }

  private const CATEGORY_FILTER = ['all', 'F1', 'F2', 'F3', 'PL1', 'PL6'];

  public function getWIPFilterSummary(Request $request)
  {
    $filterType = trim($request->input('filterType', ''));
    $filteringCondition = trim($request->input('filteringCondition', ''));
    ['start' => $startDate, 'end' => $endDate] = $this->parseDateRange($request->input('date', ''));

    return $this->wipService->getWIPFilterSummary($filterType,  $startDate, $endDate, $filteringCondition);
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

  public function getWIPQuantityAndLotsTotal(Request $request)
  {
    $workweek  = $request->input('workweek', '');
    $useWorkweek = !empty($workweek);
    ['start' => $startDate, 'end' => $endDate] = $this->parseDateRange($request->input('dateRange', ''));

    return $this->wipService->getWIPQuantityAndLotsTotal($useWorkweek, $workweek, $startDate, $endDate);
  }

  public function wipTable()
  {
    return Inertia::render('WIPTable');
  }
}

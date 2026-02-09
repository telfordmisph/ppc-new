<?php

namespace App\Http\Controllers;

use App\Models\PackageCapacity;
use App\Repositories\AnalogCalendarRepository;
use App\Traits\ParseRequestTrait;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Services\PackageCapacityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PackageBodySizeCapacityController extends Controller
{
  public function index(Request $request)
  {
    return Inertia::render('PackageBodySizeCapacityList', []);
  }

  public function bodySizes(Request $request)
  {
    return Inertia::render('PackageBodySizeCapacityBodySizeList', []);
  }

  public function machines(Request $request)
  {
    return Inertia::render('PackageBodySizeCapacityMachineList', []);
  }
}

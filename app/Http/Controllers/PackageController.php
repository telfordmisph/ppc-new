<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Traits\ParseRequestTrait;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Repositories\PackageGroupRepository;

class PackageController extends Controller
{
  use ParseRequestTrait;
  protected $packageGroupRepo;

  public function __construct(PackageGroupRepository $packageGroupRepo)
  {
    $this->packageGroupRepo = $packageGroupRepo;
  }

  public function getAllPackages(Request $request)
  {
    return Package::all();
  }
}

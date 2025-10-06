<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{

    public function index(Request $request)
    {
        return Inertia::render('Dashboard');
    }

    public function wipDashboardIndex(Request $request)
    {
        return Inertia::render('WIPDashboard');
    }

    public function pickupDashboardIndex(Request $request)
    {
        return Inertia::render('PickupDashboard');
    }

    public function residualDashboardIndex(Request $request)
    {
        return Inertia::render('ResidualDashboard');
    }
}

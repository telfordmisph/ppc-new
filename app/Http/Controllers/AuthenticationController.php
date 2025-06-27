<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthenticationController extends Controller
{
    public function setSession(Request $request)
    {
        $token = $request->input('queryToken');

        $currentUser = DB::connection('authify')
            ->table('authify_sessions')
            ->where('token', $token)
            ->first();

        session([
            'emp_data' => [
                'token' => $currentUser->token,
                'emp_id' => $currentUser->emp_id,
                'emp_name' => $currentUser->emp_name,
                'emp_firstname' => $currentUser->emp_firstname,
                'emp_jobtitle' => $currentUser->emp_jobtitle,
                'emp_dept' => $currentUser->emp_dept,
                'emp_prodline' => $currentUser->emp_prodline,
                'emp_station' => $currentUser->emp_station,
                'generated_at' => $currentUser->generated_at,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        session()->forget('emp_data');
        session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

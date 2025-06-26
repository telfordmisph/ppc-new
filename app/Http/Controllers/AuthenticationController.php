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
    public function index(Request $request)
    {
        // $this->clearSession($request);

        $token = $request->query('token');

        if ($token) {
            // $this->validateToken($token);
            return redirect()->away('https://www.google.com');
        }

        return Inertia::render('Authentication/Login');
    }

    public function setSession(Request $request)
    {
        $token = $request->input('token');

        dd($token);

        // session([
        //     'emp_data' => [
        //         'token' => $data['data']['token'],
        //         'emp_id' => $data['data']['emp_id'],
        //         'emp_name' => $data['data']['emp_name'],
        //         'emp_firstname' => $data['data']['emp_firstname'],
        //         'emp_jobtitle' => $data['data']['emp_jobtitle'],
        //         'emp_dept' => $data['data']['emp_dept'],
        //         'emp_prodline' => $data['data']['emp_prodline'],
        //         'emp_station' => $data['data']['emp_station'],
        //         'generated_at' => $data['data']['generated_at'],
        //     ]
        // ]);

        // return redirect()->route('dashboard');
        // return Inertia::render('Authentication/Login');
    }

    public function logout(Request $request)
    {
        DB::connection('authify')
            ->table('authify_sessions')
            ->where('token', session()->getId())
            ->delete();


        $this->clearSession($request);

        return redirect()->route('login-page');
    }

    protected function validateToken($token)
    {
        // // CHANGE WHEN DEPLOYING
        // $SSO_response = Http::post("http://127.0.0.1:8001/api/authify/validate?token=$token");

        // if ($SSO_response->failed()) {
        //     return back()->withErrors([
        //         'general' => 'Invalid credentials or SSO service is unavailable.',
        //     ]);
        // }

        return redirect()->route('login-page');
    }

    protected function clearSession($request)
    {
        session()->forget('emp_data');
        session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // for logging

class SessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('SessionMiddleware triggered', [
            'path'   => $request->path(),
            'url'   => $request->url(),
            'method' => $request->method(),
            'route'  => optional($request->route())->getActionName(),
        ]);


        $tokenFromQuery   = $request->query('key');
        $tokenFromSession = session('emp_data.token');
        $tokenFromCookie  = $request->cookie('sso_token');

        $token = $tokenFromQuery ?? $tokenFromSession ?? $tokenFromCookie;

        $source = $tokenFromQuery ? 'query' : ($tokenFromSession ? 'session' : ($tokenFromCookie ? 'cookie' : 'none'));
        Log::info("AuthMiddleware: token source = {$source}, token = {$token}");

        if (!$token) {
            return $this->redirectToLogin($request);
        }

        $existing = session('emp_data');
        if ($existing && $existing['token'] === $token) {
            if ($tokenFromQuery) {
                return redirect($request->url());
            }
            return $next($request);
        }

        $currentUser = DB::connection('authify')
            ->table('authify_sessions')
            ->where('token', $token)
            ->first();

        if (!$currentUser) {
            session()->forget('emp_data');
            return $this->redirectToLogin($request);
        }

        session(['emp_data' => [
            'token'         => $currentUser->token,
            'emp_id'        => $currentUser->emp_id,
            'emp_name'      => $currentUser->emp_name,
            'emp_firstname' => $currentUser->emp_firstname,
            'emp_jobtitle'  => $currentUser->emp_jobtitle,
            'emp_dept'      => $currentUser->emp_dept,
            'emp_prodline'  => $currentUser->emp_prodline,
            'emp_station'   => $currentUser->emp_station,
            'generated_at'  => $currentUser->generated_at,
        ]]);

        // if ($tokenFromQuery) {
        //     return redirect($request->url());
        // }
        if ($tokenFromQuery && (!$existing || $existing['token'] !== $token)) {
            return redirect($request->url());
        }

        return $next($request);
    }

    private function redirectToLogin(Request $request)
    {
        $redirectUrl = urlencode($request->fullUrl());
        return redirect("http://192.168.1.27:8080/authify/public/login?redirect={$redirectUrl}");
    }
}

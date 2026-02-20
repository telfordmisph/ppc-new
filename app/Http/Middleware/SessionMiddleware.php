<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        if (!$token) {
            return $this->redirectToLogin($request);
        }

        $cacheKey = 'authify_user_' . $token;

        $user = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($token) {
            return DB::connection('authify')
                ->table('authify_sessions')
                ->where('token', $token)
                ->first();
        });

        if (!$user) {
            session()->forget('emp_data');
            return $this->redirectToLogin($request);
        }

        Log::info("user: " . json_encode($user));
        session(['emp_data' => [
            'token'         => $user->token,
            'emp_id'        => $user->emp_id,
            'emp_name'      => $user->emp_name,
            'emp_firstname' => $user->emp_firstname,
            'emp_jobtitle'  => $user->emp_jobtitle,
            'emp_dept'      => $user->emp_dept,
            'emp_prodline'  => $user->emp_prodline,
            'emp_station'   => $user->emp_station,
            'generated_at'  => $user->generated_at,
        ]]);

        $request->attributes->set('auth_user', $user);
        if ($request->query('key')) {
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

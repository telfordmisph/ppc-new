<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        Log::info('AuthMiddleware triggered', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route_name' => optional($request->route())->getName(),
            'route_action' => optional($request->route())->getActionName(),
            'all_params' => $request->all(),
        ]);

        $user = $request->attributes->get('auth_user');

        if (!$user) {
            abort(403);
        }

        $dept = strtolower($user->emp_dept ?? '');

        if (!in_array($dept, array_map('strtolower', ['ppc', 'mis']))) {
            return Inertia::render('Forbidden');
        }

        $request->attributes->set('emp_id', $user->emp_id);
        return $next($request);

        // $token = $request->query('key') ?? $request->bearerToken() ?? session('emp_data.token');
        // if (!$token) {
        //     $redirectUrl = urlencode($request->fullUrl());
        //     return redirect("http://192.168.1.27:8080/authify/public/login?redirect={$redirectUrl}");
        // }

        // Log::info("session('emp_data.token'):" . session('emp_data.token'));
        // Log::info("bearer:" . $token);
        // Log::info(session()->all());

        // $cacheKey = 'authify_user_' . $token;

        // $currentUser = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($token) {
        //     return DB::connection('authify')
        //         ->table('authify.authify_sessions')
        //         ->where('token', $token)
        //         ->first();
        // });

        // Log::info("Current User: " . json_encode($currentUser));

        // $dept = strtolower($currentUser?->emp_dept ?? '');

        // if ($dept !== 'ppc' && $dept !== 'mis') {
        //     return Inertia::render('Forbidden');
        // }

    }
}

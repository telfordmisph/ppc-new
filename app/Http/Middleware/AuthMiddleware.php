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

        // TODO multiple call of api's repeating this check

        $token = $request->query('key') ?? $request->bearerToken() ?? session('emp_data.token');
        if (!$token) {
            $redirectUrl = urlencode($request->fullUrl());
            return redirect("http://192.168.1.27:8080/authify/public/login?redirect={$redirectUrl}");
        }

        Log::info("session('emp_data.token'):" . session('emp_data.token'));
        Log::info("bearer:" . $token);
        Log::info(session()->all());

        $cacheKey = 'authify_user_' . $token;

        $currentUser = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($token) {
            return DB::connection('authify')
                ->table('authify.authify_sessions')
                ->where('token', $token)
                ->first();
        });

        $department = $currentUser->emp_dept;

        if ($department !== "PPC" && $department !== "MIS") {
            return Inertia::render('Forbidden');
        }

        // $role = strtolower(trim($currentUser->emp_jobtitle));
        // Log::info("role: " . json_encode($role));

        // $rolesConfig = config('roles');

        // $rolesConfigLower = [];
        // foreach ($rolesConfig as $key => $permissions) {
        //     $rolesConfigLower[strtolower($key)] = array_map('strtolower', $permissions);
        // }

        // if ($permission && !array_key_exists($role, $rolesConfigLower)) {
        //     Log::info("Role not found: " . json_encode($rolesConfigLower));
        //     return Inertia::render('Forbidden');
        // }

        // if ($permission && !in_array(strtolower($permission), $rolesConfigLower[$role])) {
        //     Log::info("Permission not allowed: " . json_encode($rolesConfigLower[$role]));
        //     return Inertia::render('Forbidden');
        // }

        $request->attributes->set('emp_id', $currentUser->emp_id);
        return $next($request);
    }
}

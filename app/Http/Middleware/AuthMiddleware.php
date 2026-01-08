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

        // Log::info("Current User: " . json_encode($currentUser));

        // $role = strtolower(trim($currentUser->emp_jobtitle));
        // Log::info("role: " . json_encode($role));

        // $rolesConfig = config('roles');
        // Log::info("rolesConfig: " . json_encode($rolesConfig));

        // if (!array_key_exists($role, $rolesConfig)) {
        //     Log::info("ddd: " . json_encode($rolesConfig));

        //     return Inertia::render('Forbidden');
        // }

        // if ($permission && !in_array($permission, $rolesConfig[$role])) {
        //     Log::info("xxx: " . json_encode($rolesConfig));

        //     return Inertia::render('Forbidden');
        // }

        $role = strtolower(trim($currentUser->emp_jobtitle));
        Log::info("role: " . json_encode($role));

        $rolesConfig = config('roles');
        Log::info("rolesConfig: " . json_encode($rolesConfig));

        // Make a lowercase version of rolesConfig keys for comparison
        $rolesConfigLower = [];
        foreach ($rolesConfig as $key => $permissions) {
            $rolesConfigLower[strtolower($key)] = array_map('strtolower', $permissions);
        }

        if (!array_key_exists($role, $rolesConfigLower)) {
            Log::info("Role not found: " . json_encode($rolesConfigLower));
            return Inertia::render('Forbidden');
        }

        if ($permission && !in_array(strtolower($permission), $rolesConfigLower[$role])) {
            Log::info("Permission not allowed: " . json_encode($rolesConfigLower[$role]));
            return Inertia::render('Forbidden');
        }


        // Log::info($request->headers->all());

        // COMMENT OUT IF NO SPECIFIC DEPT OR JOB TITLE
        // if (session('emp_data') && !in_array(session('emp_data')['emp_dept'], ['MIS', 'Human Resource'])) {
        //     Log::info("Unauthorized: " . session('emp_data')['emp_dept']);
        //     return redirect()->route('unauthorized');
        // }

        // if (!$currentUser) {
        //     if ($request->expectsJson()) {
        //         return response()->json(['error' => 'Invalid token'], 401);
        //     }

        //     $redirectUrl = urlencode($request->fullUrl());
        //     Log::info('Redirect URL: ' . $redirectUrl);
        //     return redirect("http://192.168.1.27:8080/authify/public/login?redirect={$redirectUrl}");
        // }

        // Assign system roles
        // $systemRoles = [];

        // // Supervisor
        // if (stripos($currentUser->emp_jobtitle, 'MIS Senior Supervisor') !== false) {
        //     $systemRoles[] = 'supervisor';
        // }

        // // Support
        // if (
        //     stripos($currentUser->emp_jobtitle, 'MIS Support Technician') !== false ||
        //     stripos($currentUser->emp_jobtitle, 'Network Technician') !== false ||
        //     stripos($currentUser->emp_jobtitle, 'Network') !== false
        // ) {
        //     $systemRoles[] = 'support';
        // }

        // // Default role if none assigned
        // if (empty($systemRoles)) {
        //     $systemRoles[] = 'N/A';
        // }

        // Log::info("$currentUser->token" . $currentUser->token);
        // Log::info("$currentUser->emp_id" . $currentUser->emp_id);
        // Log::info("$currentUser->emp_name" . $currentUser->emp_name);
        // Log::info("$currentUser->emp_position" . $currentUser->emp_position);
        // Log::info("$currentUser->emp_firstname" . $currentUser->emp_firstname);
        // Log::info("$currentUser->emp_jobtitle" . $currentUser->emp_jobtitle);
        // Log::info("$currentUser->emp_dept" . $currentUser->emp_dept);
        // Log::info("$currentUser->emp_prodline" . $currentUser->emp_prodline);
        // Log::info("$currentUser->emp_station" . $currentUser->emp_station);
        // Log::info("$currentUser->generated_at" . $currentUser->generated_at);

        // Set session
        // session(['emp_data' => [
        //     'token' => $currentUser->token,
        //     'emp_id' => $currentUser->emp_id,
        //     'emp_name' => $currentUser->emp_name,
        //     'emp_position' => $currentUser->emp_position,
        //     'emp_firstname' => $currentUser->emp_firstname,
        //     'emp_jobtitle' => $currentUser->emp_jobtitle,
        //     'emp_dept' => $currentUser->emp_dept,
        //     'emp_prodline' => $currentUser->emp_prodline,
        //     'emp_station' => $currentUser->emp_station,
        //     'generated_at' => $currentUser->generated_at,
        //     // 'emp_system_roles' => $systemRoles,
        // ]]);
        $request->attributes->set('emp_id', $currentUser->emp_id);
        return $next($request);
    }
}

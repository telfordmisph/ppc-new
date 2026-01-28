<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiPermissionMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next, $permission = null): Response
  {
    Log::info('permission: ' . $permission);
    $role = $request->attributes->get('role');
    $rolesConfig = config('roles');

    $rolesConfigLower = [];
    foreach ($rolesConfig as $key => $permissions) {
      $rolesConfigLower[strtolower($key)] = array_map('strtolower', $permissions);
    }

    // Log::info("rolesConfigLower: " . json_encode($rolesConfigLower));
    // Log::info("rolesConfigLower: " . json_encode($rolesConfigLower));
    // Log::info("rolesConfigLower: " . json_encode(!array_key_exists($role, $rolesConfigLower)));
    if ($permission !== null) {
      if (!isset($rolesConfigLower[$role]) || !in_array(strtolower($permission), $rolesConfigLower[$role])) {
        return response()->json([
          'status' => 'error',
          'error' => 'Unauthorized',
          'message' => 'You are not authorized to perform this action.',
        ], 403);
      }
    }

    return $next($request);
  }
}

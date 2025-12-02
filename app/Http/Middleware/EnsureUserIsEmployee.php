<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEmployee
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || ($request->user()->role !== 'employee' && $request->user()->role !== 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بالوصول لهذه الصفحة',
            ], 403);
        }

        return $next($request);
    }
}

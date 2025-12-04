<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Visit;
use Carbon\Carbon;

use Illuminate\Support\Facades\Cache;

class TrackVisitors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $today = Carbon::today()->toDateString();
        $cacheKey = "visitor_{$ip}_{$today}";

        // Check if this IP has already been tracked today in cache
        if (!Cache::has($cacheKey)) {
            // Check database just in case (optional, but good for consistency if cache clears)
            $visitExists = Visit::where('ip_address', $ip)
                                ->whereDate('visited_at', Carbon::today())
                                ->exists();

            if (!$visitExists) {
                Visit::create([
                    'ip_address' => $ip,
                    'user_agent' => $request->userAgent(),
                    'visited_at' => Carbon::now(),
                ]);
            }

            // Store in cache for 24 hours (or until end of day)
            Cache::put($cacheKey, true, now()->addDay());
        }

        return $next($request);
    }
}

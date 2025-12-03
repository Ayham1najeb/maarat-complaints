<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Visit;
use Carbon\Carbon;

class TrackVisitors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ignore API calls from the frontend app itself if possible, but for now we track all requests
        // We can filter out some user agents or specific routes if needed.
        // A simple way is to track unique IPs per day.

        $ip = $request->ip();
        $today = Carbon::today();

        // Check if this IP has already visited today
        $visitExists = Visit::where('ip_address', $ip)
                            ->where('visited_at', $today)
                            ->exists();

        if (!$visitExists) {
            Visit::create([
                'ip_address' => $ip,
                'user_agent' => $request->userAgent(),
                'visited_at' => $today,
            ]);
        }

        return $next($request);
    }
}

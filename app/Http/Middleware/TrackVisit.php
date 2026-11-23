<?php

namespace App\Http\Middleware;

use App\Models\Computer;
use App\Models\Tracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $statusFilter = $request->routeIs('computers.index') ? $request->query('status') : null;

        if (! is_string($statusFilter) || ! array_key_exists($statusFilter, Computer::STATUSES)) {
            $statusFilter = null;
        }

        Tracker::create([
            'page' => $request->path(),
            'ip' => $request->ip(),
            'status' => $response->getStatusCode(),
            'visited_at' => now(),
            'status_filter' => $statusFilter,
        ]);

        return $response;
    }
}

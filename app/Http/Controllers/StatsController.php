<?php

namespace App\Http\Controllers;

use App\Models\Tracker;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function pages(Request $request): View
    {
        $type = $request->query('type', 'page');

        if (! in_array($type, ['page', 'ip'], true)) {
            abort(400, 'Невідомий тип групування.');
        }

        $rows = Tracker::query()
            ->selectRaw("{$type}, COUNT(*) AS hits, MAX(visited_at) AS last_visit")
            ->groupBy($type)
            ->orderByDesc('hits')
            ->get();

        return view('stats.pages', compact('rows', 'type'));
    }

    public function detail(Request $request): View
    {
        $type = $request->query('type', 'page');
        $item = $request->query('item');

        if (! in_array($type, ['page', 'ip'], true)) {
            abort(400, 'Невідомий тип фільтрації.');
        }

        $rows = Tracker::query()
            ->when($item !== null, fn ($query) => $query->where($type, $item))
            ->orderByDesc('visited_at')
            ->paginate(15)
            ->withQueryString();

        return view('stats.detail', compact('rows', 'type', 'item'));
    }

    public function filters(): View
    {
        $rows = Tracker::query()
            ->selectRaw('status_filter, COUNT(*) AS hits')
            ->whereNotNull('status_filter')
            ->groupBy('status_filter')
            ->orderByDesc('hits')
            ->get();

        return view('stats.filters', compact('rows'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ViewStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request, ViewStats $stats): View
    {
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 7;

        return view('admin.analytics.index', [
            'days' => $days,
            'total' => $stats->total($days),
            'songs' => $stats->top('song', $days),
            'artists' => $stats->top('artist', $days),
            'albums' => $stats->top('album', $days),
        ]);
    }
}

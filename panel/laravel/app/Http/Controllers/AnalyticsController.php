<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Shlink\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AnalyticsController extends Controller
{
    public function show(Request $request, AnalyticsService $analyticsService, string $shortCode): View
    {
        $visits = $analyticsService->getShortUrlVisits($shortCode, [
            'startDate' => $request->query('startDate'),
            'endDate' => $request->query('endDate'),
            'page' => $request->integer('page') ?: null,
            'itemsPerPage' => $request->integer('itemsPerPage') ?: null,
            'excludeBots' => true,
        ]);

        return view('analytics.show', [
            'shortCode' => $shortCode,
            'visits' => $visits,
        ]);
    }
}

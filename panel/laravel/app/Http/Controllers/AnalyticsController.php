<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Support\Shlink\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

final class AnalyticsController extends Controller
{
    public function show(Request $request, string $shortCode): View
    {
        $link = ShortLink::query()
            ->where('user_id', $request->user()->id)
            ->where('shlink_short_code', $shortCode)
            ->first();

        abort_unless($link instanceof ShortLink, 404);

        $visits = [];
        $analyticsError = null;
        $analyticsService = app(AnalyticsService::class);

        try {
            $visits = $analyticsService->getShortUrlVisits($shortCode, [
                'domain' => $link->domain,
                'startDate' => $request->query('startDate'),
                'endDate' => $request->query('endDate'),
                'page' => $request->integer('page') ?: null,
                'itemsPerPage' => min(100, max(1, $request->integer('itemsPerPage') ?: 25)),
                'excludeBots' => true,
            ]);
        } catch (Throwable $e) {
            $analyticsError = 'As métricas ainda não estão disponíveis. O link continua ativo e tentaremos novamente quando o motor responder.';
            Log::warning('analytics.fetch_failed', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => $request->user()->id,
                'short_code' => $shortCode,
                'exception' => $e::class,
            ]);
        }

        return view('analytics.show', [
            'link' => $link,
            'shortCode' => $shortCode,
            'visits' => $visits,
            'analyticsError' => $analyticsError,
            'startDate' => (string) $request->query('startDate', ''),
            'endDate' => (string) $request->query('endDate', ''),
        ]);
    }
}

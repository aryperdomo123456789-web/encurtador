<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Support\Shlink\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AnalyticsController extends Controller
{
    public function show(Request $request, string $shortCode): View
    {
        $workspaceId = $this->workspaceId($request);
        $link = ShortLink::query()
            ->when(
                $workspaceId !== null,
                fn ($query) => $query->where('workspace_id', $workspaceId),
                fn ($query) => $query->where('user_id', $request->user()->id)
            )
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

    public function export(Request $request, string $shortCode): StreamedResponse
    {
        $workspaceId = $this->workspaceId($request);
        $link = ShortLink::query()
            ->when(
                $workspaceId !== null,
                fn ($query) => $query->where('workspace_id', $workspaceId),
                fn ($query) => $query->where('user_id', $request->user()->id)
            )
            ->where('shlink_short_code', $shortCode)
            ->first();

        abort_unless($link instanceof ShortLink, 404);

        $visits = app(AnalyticsService::class)->getShortUrlVisits($shortCode, [
            'domain' => $link->domain,
            'startDate' => $request->query('startDate'),
            'endDate' => $request->query('endDate'),
            'page' => 1,
            'itemsPerPage' => 1000,
            'excludeBots' => true,
        ]);
        $items = collect(data_get($visits, 'visits') ?? data_get($visits, 'data') ?? []);

        return response()->streamDownload(function () use ($items): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['data', 'tipo', 'pais', 'cidade', 'referenciador', 'dispositivo', 'navegador', 'sistema']);

            foreach ($items as $item) {
                fputcsv($handle, [
                    data_get($item, 'visit.date') ?? data_get($item, 'date'),
                    data_get($item, 'visit.type') ?? data_get($item, 'type'),
                    data_get($item, 'visitLocation.countryName') ?? data_get($item, 'countryName'),
                    data_get($item, 'visitLocation.cityName') ?? data_get($item, 'cityName'),
                    data_get($item, 'referrer.url') ?? data_get($item, 'referrer'),
                    data_get($item, 'device.type') ?? data_get($item, 'deviceType'),
                    data_get($item, 'userAgent.browserName') ?? data_get($item, 'browserName'),
                    data_get($item, 'userAgent.osName') ?? data_get($item, 'osName'),
                ]);
            }

            fclose($handle);
        }, 'melink-'.$shortCode.'-analytics.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="melink-'.$shortCode.'-analytics.csv"',
        ]);
    }

    private function workspaceId(Request $request): ?int
    {
        $selected = (int) $request->session()->get('workspace_id', 0);
        $query = $request->user()->workspaces()->where('workspaces.status', 'active');
        $workspaceId = $selected > 0
            ? $query->whereKey($selected)->value('workspaces.id')
            : $query->orderBy('workspaces.id')->value('workspaces.id');

        return $workspaceId === null ? null : (int) $workspaceId;
    }
}

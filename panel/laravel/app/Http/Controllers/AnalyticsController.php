<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Support\Shlink\AnalyticsService;
use App\Support\Shlink\ShlinkException;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function show(Request $request, string $shortCode, AnalyticsService $analytics)
    {
        $link = ShortLink::where('shlink_short_code', $shortCode)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $clicksByDay = [];
        $topCountries = [];
        $topReferers = [];
        $topBrowsers = [];
        $summary = ['total' => 0, 'last7' => 0, 'today' => 0];

        try {
            $data = $analytics->visits($link->shlink_short_code);
            $visits = $data['visits']['data'] ?? $data['data'] ?? [];

            $summary['total'] = count($visits);
            $now = Carbon::now();
            $sevenDaysAgo = $now->copy()->subDays(7);

            for ($i = 6; $i >= 0; $i--) {
                $clicksByDay[$now->copy()->subDays($i)->format('Y-m-d')] = 0;
            }

            foreach ($visits as $v) {
                $date = isset($v['date']) ? Carbon::parse($v['date']) : null;
                if ($date) {
                    $key = $date->format('Y-m-d');
                    if (isset($clicksByDay[$key])) {
                        $clicksByDay[$key]++;
                    }
                    if ($date->greaterThanOrEqualTo($sevenDaysAgo)) {
                        $summary['last7']++;
                    }
                    if ($date->isToday()) {
                        $summary['today']++;
                    }
                }

                $country = $v['visitLocation']['countryName'] ?? null;
                if ($country) {
                    $topCountries[$country] = ($topCountries[$country] ?? 0) + 1;
                }
                $referer = $v['referer'] ?? null;
                if ($referer !== null) {
                    $key = $referer === '' ? 'Direto' : (parse_url($referer, PHP_URL_HOST) ?: $referer);
                    $topReferers[$key] = ($topReferers[$key] ?? 0) + 1;
                }
                $ua = $v['userAgent'] ?? null;
                if ($ua) {
                    $browser = $this->detectBrowser($ua);
                    $topBrowsers[$browser] = ($topBrowsers[$browser] ?? 0) + 1;
                }
            }

            arsort($topCountries);
            arsort($topReferers);
            arsort($topBrowsers);
            $topCountries = array_slice($topCountries, 0, 5, true);
            $topReferers = array_slice($topReferers, 0, 5, true);
            $topBrowsers = array_slice($topBrowsers, 0, 5, true);
        } catch (ShlinkException $e) {
            report($e);
        }

        return view('analytics.show', compact('link', 'summary', 'clicksByDay', 'topCountries', 'topReferers', 'topBrowsers'));
    }

    private function detectBrowser(string $ua): string
    {
        if (stripos($ua, 'Edg') !== false) return 'Edge';
        if (stripos($ua, 'Chrome') !== false) return 'Chrome';
        if (stripos($ua, 'Firefox') !== false) return 'Firefox';
        if (stripos($ua, 'Safari') !== false) return 'Safari';
        return 'Outros';
    }
}

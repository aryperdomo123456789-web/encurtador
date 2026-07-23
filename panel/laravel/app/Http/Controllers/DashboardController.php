<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Models\LinkEventLog;
use App\Models\MonthlyQuotaUsage;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();

        $totalLinks = ShortLink::where('user_id', $user->id)->count();
        $freeLinks = ShortLink::where('user_id', $user->id)->where('is_free_link', true)->count();
        $premiumLinks = ShortLink::where('user_id', $user->id)->where('is_free_link', false)->count();

        $totalClicks = LinkEventLog::whereHas('shortLink', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();

        $quota = MonthlyQuotaUsage::firstOrNew([
            'user_id' => $user->id,
            'year' => $now->year,
            'month' => $now->month,
        ]);
        $quotaUsed = $quota->free_links_created ?? 0;
        $quotaLimit = config('panel.free_link_monthly_limit', 5);

        $expiringSoon = ShortLink::where('user_id', $user->id)
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [$now, $now->copy()->addDays(3)])
            ->count();

        $recentLinks = ShortLink::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalLinks', 'freeLinks', 'premiumLinks', 'totalClicks',
            'quotaUsed', 'quotaLimit', 'expiringSoon', 'recentLinks'
        ));
    }
}

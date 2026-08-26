<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CustomerDomain;
use App\Models\MonthlyQuotaUsage;
use App\Models\ShortLink;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user === null) {
            return view('landing');
        }

        $monthStart = now('UTC')->startOfMonth();
        $nextMonthStart = (clone $monthStart)->addMonth();

        $subscription = Subscription::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $recentLinks = ShortLink::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(5)
            ->get();

        $recentDomains = CustomerDomain::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(5)
            ->get();

        $linksThisMonth = ShortLink::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$monthStart, $nextMonthStart])
            ->count();

        $freeLimit = (int) config('shlink.free_monthly_limit', 5);
        $monthlyUsage = MonthlyQuotaUsage::query()
            ->where('user_id', $user->id)
            ->where('quota_month', $monthStart->format('Y-m'))
            ->first();

        return view('dashboard', [
            'user' => $user,
            'subscription' => $subscription,
            'recentLinks' => $recentLinks,
            'recentDomains' => $recentDomains,
            'totalLinks' => ShortLink::query()->where('user_id', $user->id)->count(),
            'linksThisMonth' => $linksThisMonth,
            'remainingFreeLinks' => max(0, $freeLimit - $linksThisMonth),
            'freeLimit' => $freeLimit,
            'monthlyUsage' => $monthlyUsage,
            'totalDomains' => CustomerDomain::query()->where('user_id', $user->id)->count(),
            'activeDomains' => CustomerDomain::query()->where('user_id', $user->id)->where('status', 'active')->count(),
            'pendingDomains' => CustomerDomain::query()->where('user_id', $user->id)->where('status', 'pending_dns')->count(),
            'isPremium' => (bool) $user->isPremium(),
            'isOwner' => (bool) $user->isOwner(),
            'currentPlan' => $user->isOwner()
                ? 'Owner'
                : ($subscription?->plan?->name ?? 'Free'),
            'nextResetAt' => $nextMonthStart,
        ]);
    }
}

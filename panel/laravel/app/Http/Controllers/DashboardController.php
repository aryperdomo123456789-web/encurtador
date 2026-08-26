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

        $totalLinks = ShortLink::query()
            ->where('user_id', $user->id)
            ->count();
        $totalDomains = CustomerDomain::query()
            ->where('user_id', $user->id)
            ->count();
        $activeDomains = CustomerDomain::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->count();
        $pendingDomains = CustomerDomain::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending_dns')
            ->count();

        $freeLimit = (int) config('shlink.free_monthly_limit', 5);
        $monthlyUsage = MonthlyQuotaUsage::query()
            ->where('user_id', $user->id)
            ->where('quota_month', $monthStart->format('Y-m'))
            ->first();

        $isPremium = (bool) $user->isPremium();
        $isOwner = (bool) $user->isOwner();
        $hasCampaign = ShortLink::query()
            ->where('user_id', $user->id)
            ->where(function ($query): void {
                $query->whereNotNull('utm_source')
                    ->orWhereNotNull('utm_campaign');
            })
            ->exists();
        $hasApiToken = $user->apiTokens()->exists();

        $onboarding = [
            [
                'label' => 'Crie seu primeiro link',
                'description' => 'Coloque uma campanha no ar em menos de um minuto.',
                'done' => $totalLinks > 0,
                'href' => route('links.create'),
                'cta' => 'Criar link',
            ],
            [
                'label' => 'Marque uma campanha',
                'description' => 'Use UTMs para saber qual canal realmente performa.',
                'done' => $hasCampaign,
                'href' => $isPremium ? route('links.premium') : route('billing.index'),
                'cta' => $isPremium ? 'Criar campanha' : 'Ver Premium',
            ],
            [
                'label' => 'Conecte sua marca',
                'description' => 'Leve seus links para um domínio que o cliente reconhece.',
                'done' => $activeDomains > 0,
                'href' => $isPremium ? route('domains.index') : route('billing.index'),
                'cta' => $isPremium ? 'Gerenciar domínio' : 'Desbloquear domínio',
            ],
            [
                'label' => 'Automatize com a API',
                'description' => 'Crie integrações seguras para sua equipe ou agência.',
                'done' => $hasApiToken,
                'href' => route('api-tokens.index'),
                'cta' => 'Abrir API',
            ],
        ];
        $onboardingCompleted = count(array_filter($onboarding, static fn (array $step): bool => $step['done']));
        $onboardingTotal = count($onboarding);

        return view('dashboard', [
            'user' => $user,
            'subscription' => $subscription,
            'recentLinks' => $recentLinks,
            'recentDomains' => $recentDomains,
            'totalLinks' => $totalLinks,
            'linksThisMonth' => $linksThisMonth,
            'remainingFreeLinks' => max(0, $freeLimit - $linksThisMonth),
            'freeLimit' => $freeLimit,
            'monthlyUsage' => $monthlyUsage,
            'totalDomains' => $totalDomains,
            'activeDomains' => $activeDomains,
            'pendingDomains' => $pendingDomains,
            'isPremium' => $isPremium,
            'isOwner' => $isOwner,
            'currentPlan' => $isOwner ? 'Owner' : ($subscription?->plan?->name ?? 'Free'),
            'nextResetAt' => $nextMonthStart,
            'onboarding' => $onboarding,
            'onboardingCompleted' => $onboardingCompleted,
            'onboardingTotal' => $onboardingTotal,
        ]);
    }
}

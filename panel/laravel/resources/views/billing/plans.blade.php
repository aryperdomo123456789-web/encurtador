@extends('layouts.app')

@section('title', 'Assinatura')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold mb-2">Assinatura</h1>
    <p class="text-gray-600 mb-6">
        Estado atual:
        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium
            {{ $isPremium ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-700' }}">
            {{ $isPremium ? 'Premium' : 'Free' }}
        </span>
    </p>

    @if(!empty($flash))
        <div class="mb-4 rounded border border-blue-200 bg-blue-50 text-blue-800 px-4 py-3">
            {{ $flash }}
        </div>
    @endif

    @if(session('status'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 text-green-800 px-4 py-3">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 text-red-800 px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-4">
        @foreach($plans as $plan)
            <div class="rounded-lg border p-5 {{ $plan->slug === 'premium' ? 'border-indigo-400' : 'border-gray-200' }}">
                <h2 class="text-lg font-semibold">{{ $plan->name }}</h2>
                <ul class="mt-3 text-sm text-gray-700 space-y-1">
                    <li>Cota mensal: {{ $plan->monthly_link_quota > 0 ? $plan->monthly_link_quota . ' links' : 'ilimitada' }}</li>
                    <li>Slug customizado: {{ $plan->allow_custom_slug ? 'sim' : 'nao' }}</li>
                    <li>Dominio proprio: {{ $plan->allow_custom_domain ? 'sim' : 'nao' }}</li>
                    <li>Expiracao: {{ $plan->link_expiration_days > 0 ? $plan->link_expiration_days . ' dias' : 'sem expiracao' }}</li>
                </ul>

                @if($plan->slug === 'premium')
                    <div class="mt-4">
                        @if($isPremium)
                            <form method="POST" action="{{ route('billing.portal') }}">
                                @csrf
                                <button class="w-full rounded bg-gray-800 text-white py-2 hover:bg-gray-900">
                                    Gerenciar assinatura
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('billing.checkout') }}">
                                @csrf
                                <button class="w-full rounded bg-indigo-600 text-white py-2 hover:bg-indigo-700">
                                    Assinar Premium
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if($subscription)
        <p class="mt-6 text-xs text-gray-500">
            Assinatura Stripe: {{ $subscription->stripe_subscription_id ?? 'n/d' }} - status {{ $subscription->status }}.
        </p>
    @endif
</div>
@endsection

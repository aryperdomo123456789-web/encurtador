@extends('layouts.app')

@section('title', 'MElink | Catálogo de planos')
@section('meta_description', 'Gestão owner-only do catálogo comercial e dos limites do MElink.')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Administração comercial</span>
            <h1 class="page-title">Catálogo de planos</h1>
            <p class="page-subtitle">
                Organize preço, limites e posicionamento do MElink em um único lugar. Os valores abaixo são hipótese comercial;
                o checkout só pode cobrar um Price ID validado no servidor.
            </p>
            <div class="hero-actions">
                <a class="button primary" href="{{ route('admin.plans.create') }}">Criar plano</a>
                <a class="button secondary" href="{{ route('admin.dashboard') }}">Voltar ao admin</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Visão comercial</h2>
                        <p class="meta">Leitura operacional do catálogo atual.</p>
                    </div>
                    <span class="badge info">Owner only</span>
                </div>
                <ul class="list">
                    <li><span class="label">Planos públicos ativos</span><span class="value">{{ $summary['active'] }}</span></li>
                    <li><span class="label">Assinantes ativos</span><span class="value">{{ $summary['subscribers'] }}</span></li>
                    <li><span class="label">MRR teórico</span><span class="value">R$ {{ number_format($summary['mrrCents'] / 100, 2, ',', '.') }}</span></li>
                    <li><span class="label">Prices vinculados</span><span class="value">{{ $summary['withStripePrice'] }}</span></li>
                </ul>
            </div>
        </aside>
    </div>

    <div class="alert warning">
        <strong>Modo controlado:</strong> criar ou editar aqui altera o catálogo local. A sincronização com Stripe Test será uma ação explícita;
        nenhum segredo Stripe é salvo nesta tela e nenhum plano novo deve ir para live sem E2E aprovado.
    </div>

    <section class="table-panel section">
        <div class="section-head">
            <div>
                <h2>Planos e limites</h2>
                <p>Use a tabela para revisar preço, capacidade e estado de cobrança antes de convidar pilotos.</p>
            </div>
            <span class="badge muted">{{ $plans->count() }} registros</span>
        </div>

        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Plano</th>
                        <th>Preço</th>
                        <th>Links / mês</th>
                        <th>Cliques / mês</th>
                        <th>Domínios</th>
                        <th>Stripe</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>
                                <strong>{{ $plan->name }}</strong>
                                <div class="meta"><code>{{ $plan->code }}</code></div>
                                @if($plan->marketing_label)
                                    <div class="meta">{{ $plan->marketing_label }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $plan->is_free ? 'Grátis' : 'R$ '.number_format($plan->monthly_price_cents / 100, 2, ',', '.') }}</strong>
                                @unless($plan->is_free)<div class="meta">{{ $plan->currency }} / mês</div>@endunless
                            </td>
                            <td>{{ $plan->monthly_short_url_limit === null ? 'Ilimitado' : number_format($plan->monthly_short_url_limit, 0, ',', '.') }}</td>
                            <td>{{ $plan->monthly_click_limit === null ? 'Ilimitado' : number_format($plan->monthly_click_limit, 0, ',', '.') }}</td>
                            <td>{{ $plan->custom_domain_limit > 0 ? $plan->custom_domain_limit : 'Nenhum' }}</td>
                            <td>
                                @if($plan->is_free)
                                    <span class="badge muted">Não aplicável</span>
                                @elseif($plan->stripe_price_id)
                                    <span class="badge success">Price vinculado</span>
                                @else
                                    <span class="badge warning">Pendente</span>
                                @endif
                            </td>
                            <td>
                                @if($plan->is_active && $plan->is_public)
                                    <span class="badge success">Público</span>
                                @elseif($plan->is_active)
                                    <span class="badge info">Interno</span>
                                @else
                                    <span class="badge muted">Arquivado</span>
                                @endif
                                @if($plan->is_featured)<div class="meta" style="margin-top:6px;">Destaque</div>@endif
                            </td>
                            <td>
                                <div class="actions" style="justify-content:flex-end;">
                                    <a class="button ghost" href="{{ route('admin.plans.edit', $plan) }}">Editar</a>
                                    @if($plan->code !== 'free' && $plan->is_active)
                                        <form method="POST" action="{{ route('admin.plans.archive', $plan) }}" onsubmit="return confirm('Arquivar este plano sem apagar o histórico?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button danger" type="submit">Arquivar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty">Nenhum plano cadastrado. Crie o primeiro plano para montar o catálogo.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

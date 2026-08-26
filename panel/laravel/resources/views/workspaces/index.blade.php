@extends('layouts.app')

@section('title', 'MElink | Workspaces')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Operação em equipe</span>
            <h1 class="page-title">Workspaces que organizam sua operação.</h1>
            <p class="page-subtitle">Separe clientes, marcas e campanhas em ambientes próprios. Convide colaboradores com o menor nível de acesso necessário.</p>
        </section>
        <aside class="hero-side">
            <div class="card compact">
                <h2 class="card-title">Contexto atual</h2>
                <p class="meta">{{ $currentWorkspace?->name ?? 'Nenhum workspace' }}</p>
                <a class="button secondary" href="{{ route('api-tokens.index') }}">Gerenciar integrações</a>
            </div>
        </aside>
    </div>

    @if (session('status'))
        <div class="alert success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert error" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="grid-two">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Seus workspaces</h2>
                    <p class="meta">Alterne o contexto antes de operar campanhas.</p>
                </div>
            </div>
            @forelse ($workspaces as $workspace)
                <div style="display:flex; justify-content:space-between; gap:16px; padding:14px 0; border-bottom:1px solid rgba(15,23,42,.09); align-items:center;">
                    <div>
                        <strong>{{ $workspace->name }}</strong>
                        <div class="meta">{{ $workspace->members_count }} membro(s) · {{ $workspace->slug }}</div>
                    </div>
                    @if ((int) $currentWorkspace?->id === (int) $workspace->id)
                        <span class="badge success">Ativo</span>
                    @else
                        <form method="POST" action="{{ route('workspaces.switch', $workspace) }}">
                            @csrf
                            <button type="submit" class="button secondary">Usar workspace</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="empty">Nenhum workspace disponível para esta conta.</div>
            @endforelse
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Criar workspace</h2>
                    <p class="meta">Disponível para contas Premium.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('workspaces.store') }}" class="stack">
                @csrf
                <div class="field">
                    <label for="name">Nome da operação</label>
                    <input id="name" name="name" required maxlength="120" placeholder="Ex.: Agência Aurora">
                </div>
                <button type="submit">Criar workspace</button>
            </form>
        </div>
    </section>

    @if ($currentWorkspace)
        <section class="card" style="margin-top:24px;">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Membros de {{ $currentWorkspace->name }}</h2>
                    <p class="meta">Adicione pessoas que já possuem conta MElink.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('workspaces.members.add', $currentWorkspace) }}" class="grid-two" style="align-items:end;">
                @csrf
                <div class="field">
                    <label for="member_email">E-mail do membro</label>
                    <input id="member_email" name="email" type="email" required placeholder="cliente@empresa.com">
                </div>
                <div class="field">
                    <label for="member_role">Papel</label>
                    <select id="member_role" name="role">
                        <option value="member">Editor</option>
                        <option value="admin">Administrador</option>
                        <option value="viewer">Leitor</option>
                    </select>
                </div>
                <button type="submit">Adicionar membro</button>
            </form>
            <div style="margin-top:18px;">
                @foreach ($currentWorkspace->members()->get() as $member)
                    <div style="display:flex; justify-content:space-between; gap:16px; padding:12px 0; border-bottom:1px solid rgba(15,23,42,.09);">
                        <span>{{ $member->name }} · {{ $member->email }}</span>
                        <span class="badge info">{{ $member->pivot->role }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection

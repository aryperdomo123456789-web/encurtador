@extends('layouts.guest')

@section('title', 'MElink | Confirmar e-mail')

@section('content')
    <span class="eyebrow">Última etapa</span>
    <h1>Confirme seu e-mail</h1>
    <p>Enviamos um link de confirmação para o endereço cadastrado. Confirme agora para liberar o painel e manter sua conta protegida.</p>

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

    <form method="POST" action="{{ route('verification.send') }}" class="stack">
        @csrf
        <button type="submit">Reenviar link de confirmação</button>
    </form>

    <p class="help">Não encontrou? Confira spam, promoções e o endereço cadastrado. O link expira por segurança.</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="button-secondary">Sair e usar outra conta</button>
    </form>
@endsection

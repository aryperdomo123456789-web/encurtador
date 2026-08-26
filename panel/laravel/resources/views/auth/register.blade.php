@extends('layouts.guest')

@section('title', 'MElink | Criar conta')

@section('content')
    <span class="eyebrow">Cadastro</span>
    <h1>Criar sua conta</h1>
    <p>Abra seu acesso ao painel para começar a criar links, domínios e acompanhar métricas.</p>

    <div style="height: 18px;"></div>

    @if ($errors->any())
        <div class="alert error" role="alert">
            <strong>Revise os dados antes de continuar.</strong>
            <ul style="margin: 10px 0 0 18px; padding: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.attempt') }}" class="stack" novalidate>
        @csrf

        <div class="field">
            <label for="name">Nome</label>
            <input id="name" name="name" type="text" required autocomplete="name" value="{{ old('name') }}" placeholder="Seu nome">
        </div>

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}" placeholder="voce@empresa.com">
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Mínimo de 8 caracteres">
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmar senha</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Digite a senha novamente">
        </div>

        <button type="submit">Criar conta</button>
    </form>

    <p class="help">
        Já tem conta? <a href="{{ route('login') }}">Entrar no painel</a>.
    </p>
@endsection

@extends('layouts.guest')

@section('title', 'MElink | Login')

@section('content')
    <span class="eyebrow">Acesso restrito</span>
    <h1>Entrar no painel</h1>
    <p>Use suas credenciais administrativas para acessar links, domínios, cobrança e métricas da operação.</p>

    <div style="height: 18px;"></div>

    @if ($errors->any())
        <div class="alert error" role="alert">
            <strong>Não foi possível entrar.</strong>
            <ul style="margin: 10px 0 0 18px; padding: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}" class="stack" novalidate>
        @csrf

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="email" value="{{ old('email') }}">
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>

        <label class="remember">
            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            Manter conectado neste dispositivo
        </label>

        <button type="submit">Entrar</button>
    </form>

    <p class="help"><a href="{{ route('password.request') }}">Esqueci minha senha</a></p>

    <p class="help">
        Ainda sem acesso? <a href="{{ route('register') }}">Crie sua conta</a> ou fale com a equipe responsável por <code>{{ config('panel.host', 'me.vr766.com') }}</code>.
    </p>
@endsection

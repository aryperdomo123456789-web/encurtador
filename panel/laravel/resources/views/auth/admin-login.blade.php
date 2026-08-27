@extends('layouts.guest')

@section('title', 'MElink | Acesso do proprietário')

@section('content')
    <span class="eyebrow">Área protegida</span>
    <h1>Acesso do proprietário</h1>
    <p>Entre para administrar usuários, marca, auditoria e a operação do MElink.</p>

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
            <label for="email">E-mail do proprietário</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username" value="{{ old('email') }}">
        </div>

        <div class="field">
            <label for="password">Senha</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>

        <label class="remember">
            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            Manter conectado neste dispositivo
        </label>

        <button type="submit">Entrar na administração</button>
    </form>

    <p class="help"><a href="{{ route('password.request') }}">Esqueci minha senha</a></p>
    <p class="help"><a href="{{ config('app.url') }}/login">Voltar ao acesso de usuários</a></p>
@endsection

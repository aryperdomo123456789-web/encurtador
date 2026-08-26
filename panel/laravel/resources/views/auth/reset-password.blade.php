@extends('layouts.guest')

@section('title', 'MElink | Nova senha')

@section('content')
    <span class="eyebrow">Acesso seguro</span>
    <h1>Crie uma nova senha</h1>
    <p>Escolha uma senha forte para recuperar o acesso ao seu painel.</p>

    @if ($errors->any())
        <div class="alert error" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="stack" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email', $email) }}">
        </div>

        <div class="field">
            <label for="password">Nova senha</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Mínimo de 8 caracteres">
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmar nova senha</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        </div>

        <button type="submit">Salvar nova senha</button>
    </form>

    <p class="help"><a href="{{ route('login') }}">Voltar para o login</a></p>
@endsection

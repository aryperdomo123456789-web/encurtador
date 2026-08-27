@extends('layouts.guest')

@section('title', 'MElink | Recuperar senha')

@section('content')
    <span class="eyebrow">Acesso seguro</span>
    <h1>Recupere sua senha</h1>
    <p>Informe o e-mail da sua conta. Se ele estiver cadastrado, enviaremos um link seguro para criar uma nova senha.</p>

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

    <form method="POST" action="{{ route('password.email') }}" class="stack" novalidate>
        @csrf
        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email" required autocomplete="email" value="{{ old('email') }}" placeholder="voce@empresa.com">
        </div>
        <button type="submit">Enviar link seguro</button>
    </form>

    <p class="help"><a href="{{ route('login') }}">Voltar para o login</a></p>
@endsection

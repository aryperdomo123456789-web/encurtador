@extends('layouts.app')

@section('title', 'MElink | Novo Rich Preview')
@section('meta_description', 'Crie uma página de compartilhamento com prévia rica e link final.')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Admin do dono</span>
            <h1 class="page-title">Novo rich preview</h1>
            <p class="page-subtitle">
                Suba uma imagem ou cole uma URL, escreva o texto e defina para onde o clique deve levar.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('admin.rich-previews.index') }}">Voltar à lista</a>
            </div>
        </section>
    </div>

    @include('admin.rich-previews.partials.form', [
        'richPreview' => $richPreview,
        'formAction' => route('admin.rich-previews.store'),
        'formMethod' => 'POST',
        'buttonLabel' => 'Criar rich preview',
    ])
@endsection

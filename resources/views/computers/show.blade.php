@extends('layouts.app')

@section('title', 'CyberZone - '.$computer->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/computers.css') }}">
@endpush

@section('content')
<section class="site-page computer-page computer-form-page">
    <h1>{{ $computer->name }}</h1>
    <dl class="computer-specs">
        <div><dt>Процесор</dt><dd>{{ $computer->cpu }}</dd></div>
        <div><dt>Відеокарта</dt><dd>{{ $computer->gpu }}</dd></div>
        <div><dt>RAM</dt><dd>{{ $computer->ram_gb }} ГБ</dd></div>
        <div><dt>Накопичувач</dt><dd>{{ $computer->storage_gb }} ГБ</dd></div>
        <div><dt>Ціна за годину</dt><dd>{{ number_format((float) $computer->hourly_rate, 2, ',', ' ') }} грн</dd></div>
        <div><dt>Статус</dt><dd><span class="computer-status computer-status-{{ $computer->status }}">{{ \App\Models\Computer::STATUSES[$computer->status] ?? $computer->status }}</span></dd></div>
    </dl>
    <div class="computer-actions">
        <a class="site-button" href="{{ route('computers.edit', $computer) }}">Редагувати</a>
        <a class="site-button computer-button-secondary" href="{{ route('computers.index') }}">До списку</a>
        <form method="POST" action="{{ route('computers.destroy', $computer) }}"
              onsubmit="return confirm('Видалити цей ПК?')">
            @csrf
            @method('DELETE')
            <button class="site-button computer-button-danger" type="submit">Видалити</button>
        </form>
    </div>
</section>
@endsection

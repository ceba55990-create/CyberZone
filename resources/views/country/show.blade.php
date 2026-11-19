@extends('layouts.app')

@section('title', 'CyberZone - '.$country->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/country.css') }}">
@endpush

@section('content')
<section class="site-page country-page country-form-page">
    <h1>{{ $country->name }}</h1>

    <dl class="country-details">
        <div>
            <dt>Код країни</dt>
            <dd>{{ $country->code }}</dd>
        </div>
        <div>
            <dt>Населення</dt>
            <dd>{{ number_format($country->population, 0, ',', ' ') }}</dd>
        </div>
    </dl>

    <div class="country-actions">
        <a class="site-button" href="{{ route('country.edit', $country) }}">Редагувати</a>
        <a class="site-button country-button-secondary" href="{{ route('country.index') }}">До списку</a>
        <form method="POST" action="{{ route('country.destroy', $country) }}"
              onsubmit="return confirm('Видалити цю країну?')">
            @csrf
            @method('DELETE')
            <button class="site-button country-button-danger" type="submit">Видалити</button>
        </form>
    </div>
</section>
@endsection

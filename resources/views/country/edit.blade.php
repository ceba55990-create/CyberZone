@extends('layouts.app')

@section('title', 'CyberZone - Редагувати країну')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/country.css') }}">
@endpush

@section('content')
<section class="site-page country-page country-form-page">
    <h1>Редагувати країну</h1>

    <form method="POST" action="{{ route('country.update', $country) }}" novalidate>
        @csrf
        @method('PUT')
        @include('country._form')

        <div class="country-actions">
            <button class="site-button" type="submit">Зберегти зміни</button>
            <a class="site-button country-button-secondary" href="{{ route('country.index') }}">До списку</a>
        </div>
    </form>
</section>
@endsection

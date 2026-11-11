@extends('layouts.app')

@section('title', 'CyberZone - Додати ПК')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/computers.css') }}">
@endpush

@section('content')
<section class="site-page computer-page computer-form-page">
    <h1>Додати ПК</h1>
    <form method="POST" action="{{ route('computers.store') }}" novalidate>
        @csrf
        @include('computers._form')
        <div class="computer-actions">
            <button class="site-button" type="submit">Зберегти</button>
            <a class="site-button computer-button-secondary" href="{{ route('computers.index') }}">До списку</a>
        </div>
    </form>
</section>
@endsection

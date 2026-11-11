@extends('layouts.app')

@section('title', 'CyberZone - Про застосунок')

@section('content')
<section class="site-page">
    <h1>Про застосунок</h1>
    <p>
        CyberZone - навчальний вебзастосунок комп'ютерного клубу, який я розробив на Laravel 12 під час виконання лабораторних робіт.
    </p>
    <p>
        На сайті можна переглянути інформацію про комп'ютери та тарифи клубу, а також скористатися формою зворотного зв'язку.
    </p>
    <a class="site-button" href="{{ route('contact.index') }}">Зв'язатися з CyberZone</a>
</section>
@endsection

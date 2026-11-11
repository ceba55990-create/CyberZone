@extends('layouts.app')

@section('title', 'CyberZone - Дані прийнято')

@section('content')
<section class="site-page">
    <h1>Дані прийнято</h1>
    <p>Ви ввели такі дані:</p>
    <ul>
        <li><strong>Ім'я:</strong> {{ $name }}</li>
        <li><strong>Email:</strong> {{ $email }}</li>
    </ul>
    <a class="site-button" href="{{ route('entry.form') }}">Повернутися до форми</a>
</section>
@endsection

@extends('layouts.app')

@section('title', 'CyberZone - Говоримо привіт')

@section('content')
<section class="site-page">
    <h1>{{ $message }}</h1>
    <p>Вітаємо у CyberZone. Це твій простір для гри.</p>
    <a class="site-button" href="{{ route('site.say', ['message' => 'Вітаю']) }}">Сказати "Вітаю"</a>
</section>
@endsection

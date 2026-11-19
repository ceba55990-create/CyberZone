
@extends('layouts.app')

@section('title', 'CyberZone - Повідомлення прийнято')

@push('styles')
<style>
    .success-container {
        max-width: 650px;
        margin: 100px auto;
        padding: 45px 30px;
        background: #19233a;
        border: 1px solid #30334f;
        border-radius: 12px;
        text-align: center;
    }

    .success-container h1 {
        color: #c4b5fd;
        margin-bottom: 20px;
    }

    .success-container p {
        color: #cbd5e1;
        line-height: 1.8;
    }

    .success-button {
        display: inline-block;
        margin-top: 30px;
        padding: 14px 28px;
        background: #7c3aed;
        color: white;
        border-radius: 8px;
        text-decoration: none;
    }

    .success-button:hover {
        background: #6d28d9;
    }
</style>
@endpush

@section('content')
<div class="success-container">

    <h1>Форму успішно заповнено!</h1>

    <p>
        Дякуємо, {{ $name }}!
    </p>

    <p>
        Ваші контактні дані пройшли перевірку.
    </p>

    <a href="{{ route('contact.index') }}"
       class="success-button">
        Повернутися до контактів
    </a>

</div>
@endsection

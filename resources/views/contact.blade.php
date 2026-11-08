
@extends('layouts.app')

@section('title', 'CyberZone - Контакти')

@push('styles')
<style>
    .contact-page {
        max-width: 650px;
        margin: 60px auto;
        padding: 20px;
    }

    .contact-header {
        text-align: center;
        margin-bottom: 35px;
    }

    .contact-header h1 {
        color: #c4b5fd;
        font-size: 36px;
    }

    .contact-header p {
        color: #94a3b8;
        line-height: 1.6;
    }

    .contact-form {
        background: #19233a;
        padding: 35px;
        border: 1px solid #30334f;
        border-radius: 12px;
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        color: #e2e8f0;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 14px;
        border: 1px solid #475569;
        border-radius: 8px;
        background: #101827;
        color: white;
        font: inherit;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 140px;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #a78bfa;
    }

    .error {
        color: #fb7185;
        font-size: 13px;
        margin-top: 8px;
    }

    .submit-button {
        width: 100%;
        padding: 15px;
        background: #7c3aed;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
    }

    .submit-button:hover {
        background: #6d28d9;
    }
</style>
@endpush

@section('content')

<div class="contact-page">

    <div class="contact-header">
        <h1>Зв'язатися з {{ $clubName }}</h1>
        <p>
            Маєш питання щодо комп'ютерів,
            тарифів або роботи клубу?
            Напиши нам.
        </p>
    </div>

    <form action="{{ route('contact.store') }}"
          method="POST"
          class="contact-form"
          novalidate>

        @csrf

        <div class="form-group">
            <label for="name">Ваше ім'я</label>
            <input type="text"
                   id="name"
                   name="name"
                   value="{{ is_string(old('name')) ? old('name') : '' }}"
                   placeholder="Введіть ім'я">

            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Електронна пошта</label>
            <input type="email"
                   id="email"
                   name="email"
                   value="{{ is_string(old('email')) ? old('email') : '' }}"
                   placeholder="example@gmail.com">

            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="message">Повідомлення</label>
            <textarea id="message"
                      name="message"
                      placeholder="Ваше повідомлення...">{{ is_string(old('message')) ? old('message') : '' }}</textarea>

            @error('message')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="submit-button">
            Надіслати повідомлення
        </button>

    </form>
</div>

@endsection

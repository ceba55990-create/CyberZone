@extends('layouts.app')

@section('title', 'CyberZone - Форма введення даних')

@section('content')
<section class="site-page">
    <h1>Форма введення даних</h1>
    <p>Введіть ім'я та електронну пошту, щоб перевірити роботу навчальної форми.</p>

    <form method="POST" action="{{ route('entry.store') }}" class="entry-form" novalidate>
        @csrf

        <div class="entry-field">
            <label for="name">Ім'я</label>
            <input type="text" id="name" name="name" value="{{ is_string(old('name')) ? old('name') : '' }}" autocomplete="name"
                   aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                   @error('name') aria-describedby="name-error" @enderror>
            @error('name')
                <p class="entry-error" id="name-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="entry-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}" autocomplete="email"
                   aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                   @error('email') aria-describedby="email-error" @enderror>
            @error('email')
                <p class="entry-error" id="email-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="site-button">Надіслати</button>
    </form>
</section>
@endsection

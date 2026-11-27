@extends('layouts.app')

@section('title', 'CyberZone - Створити новину')

@section('content')
<section class="site-page">
    <h1>Створити новину</h1>
    <form method="POST" action="{{ route('blog.store') }}">
        @csrf
        @include('blog._form')
        <button type="submit">Зберегти</button>
    </form>
    <p><a href="{{ route('blog.index') }}">До списку новин</a></p>
</section>
@endsection

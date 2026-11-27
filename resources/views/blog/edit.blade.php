@extends('layouts.app')

@section('title', 'CyberZone - Редагувати новину')

@section('content')
<section class="site-page">
    <h1>Редагувати новину</h1>
    <form method="POST" action="{{ route('blog.update', $blog) }}">
        @csrf
        @method('PUT')
        @include('blog._form')
        <button type="submit">Зберегти зміни</button>
    </form>
    <p><a href="{{ route('blog.show', $blog) }}">До новини</a></p>
</section>
@endsection

@extends('layouts.app')

@section('title')CyberZone - {{ $content['title'] }}@endsection

@section('content')
<section class="site-page">
    <h1>{{ $content['title'] }}</h1>
    @include('blog._language')

    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    <p>Категорія: {{ $blog->category?->category_name ?? 'Без категорії' }}</p>
    <p>Автор: {{ $blog->author?->name ?? 'Не вказано' }}</p>
    <p>Дата публікації: <time datetime="{{ $blog->dt_created->toIso8601String() }}">{{ $blog->dt_created->format('d.m.Y H:i') }}</time></p>

    @if ($content['description'] !== null && $content['description'] !== '')
        <p>{{ $content['description'] }}</p>
    @endif

    <div>{!! nl2br(e($content['text'] ?? '')) !!}</div>

    <p>
        <a href="{{ route('blog.edit', $blog) }}">Редагувати</a>
        <a href="{{ route('blog.index', $queryParams) }}">До списку новин</a>
    </p>
    <form method="POST" action="{{ route('blog.destroy', $blog) }}"
          onsubmit="return confirm('Видалити цю новину?')">
        @csrf
        @method('DELETE')
        <button type="submit">Видалити</button>
    </form>
</section>
@endsection

@extends('layouts.app')

@section('title', 'CyberZone - Адміністрування новин')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
@endpush

@section('content')
<section class="site-page blog-page">
    <h1>Адміністрування новин</h1>
    @include('blog._language')
    <p>
        <a href="{{ route('blog.create') }}">Створити новину</a>
        <a href="{{ route('blog.index', $queryParams) }}">Публічний список</a>
    </p>

    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    <div class="blog-table-scroll">
        <table class="blog-table">
            <thead>
                <tr>
                    <th scope="col">№</th>
                    <th scope="col">Заголовок</th>
                    <th scope="col">Категорія</th>
                    <th scope="col">Дата</th>
                    <th scope="col">Дії</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($blogs as $blog)
                    @php($content = $blog->localizedContent($lang))
                    <tr>
                        <td>{{ $blogs->firstItem() + $loop->index }}</td>
                        <td>{{ $content['title'] }}</td>
                        <td>{{ $blog->category?->category_name ?? 'Без категорії' }}</td>
                        <td><time datetime="{{ $blog->dt_created->toIso8601String() }}">{{ $blog->dt_created->format('d.m.Y H:i') }}</time></td>
                        <td>
                            <div class="blog-actions">
                                <a href="{{ route('blog.show', ['blog' => $blog] + $queryParams) }}">Переглянути</a>
                                <a href="{{ route('blog.edit', $blog) }}">Редагувати</a>
                                <form method="POST" action="{{ route('blog.destroy', $blog) }}"
                                      onsubmit="return confirm('Видалити цю новину?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">Видалити</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Новин поки немає.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $blogs->links('blog._pagination') }}
</section>
@endsection

@extends('layouts.app')

@section('title', 'CyberZone - Новини')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
@endpush

@section('content')
<section class="site-page blog-page">
    <h1>Новини CyberZone</h1>
    @include('blog._language')
    <p><a href="{{ route('blog.create') }}">Створити новину</a></p>
    <p><a href="{{ route('blog.admin', $queryParams) }}">Адміністрування новин</a></p>

    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    <div class="blog-cards">
        @forelse ($blogs as $blog)
            @include('blog._item', ['blog' => $blog])
        @empty
            <p>Новин поки немає.</p>
        @endforelse
    </div>

    {{ $blogs->links('blog._pagination') }}
</section>
@endsection

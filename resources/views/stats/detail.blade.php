@extends('layouts.app')

@section('title', 'CyberZone - Детальна статистика відвідувань')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/stats.css') }}">
@endpush

@section('content')
<section class="site-page stats-page">
    <h1>Детальна статистика відвідувань</h1>

    <p>{{ $type === 'page' ? 'Сторінка' : 'IP-адреса' }}: <strong>{{ $item ?? 'Усі значення' }}</strong></p>

    <div class="stats-grouping">
        <a class="site-button" href="{{ route('stats.pages', ['type' => $type]) }}">До зведеної статистики</a>
    </div>

    <div class="stats-table-scroll">
        <table class="stats-table">
            <thead>
                <tr>
                    <th scope="col">Сторінка</th>
                    <th scope="col">IP-адреса</th>
                    <th scope="col">Статус</th>
                    <th scope="col">Дата і час</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row->page }}</td>
                        <td>{{ $row->ip }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->visited_at->format('d.m.Y H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Відвідувань за цим фільтром немає.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rows->hasPages())
        <nav class="stats-pagination" aria-label="Навігація сторінками">
            @if ($rows->onFirstPage())
                <span aria-disabled="true">Назад</span>
            @else
                <a href="{{ $rows->previousPageUrl() }}" rel="prev">Назад</a>
            @endif

            <span aria-current="page">Сторінка {{ $rows->currentPage() }} з {{ $rows->lastPage() }}</span>

            @if ($rows->hasMorePages())
                <a href="{{ $rows->nextPageUrl() }}" rel="next">Далі</a>
            @else
                <span aria-disabled="true">Далі</span>
            @endif
        </nav>
    @endif
</section>
@endsection

@extends('layouts.app')

@section('title', 'CyberZone - Статистика відвідувань')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/stats.css') }}">
@endpush

@section('content')
<section class="site-page stats-page">
    <h1>Статистика відвідувань</h1>

    <p><a class="site-button" href="{{ route('stats.filters') }}">Статистика фільтрів комп'ютерів</a></p>

    <div class="stats-grouping" role="group" aria-label="Групування статистики">
        <a class="site-button" href="{{ route('stats.pages', ['type' => 'page']) }}" aria-current="{{ $type === 'page' ? 'page' : 'false' }}">За сторінками</a>
        <a class="site-button" href="{{ route('stats.pages', ['type' => 'ip']) }}" aria-current="{{ $type === 'ip' ? 'page' : 'false' }}">За IP-адресами</a>
    </div>

    <div class="stats-table-scroll">
        <table class="stats-table">
            <thead>
                <tr>
                    <th scope="col">{{ $type === 'page' ? 'Сторінка' : 'IP-адреса' }}</th>
                    <th scope="col">Звернень</th>
                    <th scope="col">Останній візит</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>
                            <a href="{{ route('stats.detail', ['type' => $type, 'item' => $row->{$type}]) }}">{{ $row->{$type} }}</a>
                        </td>
                        <td>{{ $row->hits }}</td>
                        <td>{{ $row->last_visit }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Даних про відвідування ще немає.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@extends('layouts.app')

@section('title', 'CyberZone - Статистика фільтрів комп\'ютерів')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/stats.css') }}">
@endpush

@section('content')
<section class="site-page stats-page">
    <h1>Статистика фільтрів комп'ютерів</h1>

    <div class="stats-grouping">
        <a class="site-button" href="{{ route('stats.pages') }}">До зведеної статистики</a>
    </div>

    <div class="stats-table-scroll">
        <table class="stats-table">
            <thead>
                <tr>
                    <th scope="col">Статус фільтра</th>
                    <th scope="col">Кількість використань</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ \App\Models\Computer::STATUSES[$row->status_filter] ?? $row->status_filter }}</td>
                        <td>{{ $row->hits }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">Фільтри ще не використовувалися.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@extends('layouts.app')

@section('title', 'CyberZone - Комп\'ютери')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/computers.css') }}">
@endpush

@section('content')
<section class="site-page computer-page">
    <div class="computer-header">
        <h1>Комп'ютери CyberZone</h1>
        <a class="site-button" href="{{ route('computers.create') }}">Додати ПК</a>
    </div>

    @if (session('success'))
        <p class="computer-success" role="status">{{ session('success') }}</p>
    @endif

    <form class="computer-filter" method="GET" action="{{ route('computers.index') }}">
        <div>
            <label for="status-filter">Статус</label>
            <select id="status-filter" name="status">
                <option value="">Усі статуси</option>
                @foreach (\App\Models\Computer::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="site-button" type="submit">Показати</button>
        <a class="site-button computer-button-secondary" href="{{ route('computers.index') }}">Скинути</a>
    </form>

    <p>Знайдено ПК: {{ $computers->total() }}</p>

    <div class="computer-table-scroll">
        <table class="computer-table">
            <thead>
                <tr>
                    <th scope="col">Назва ПК</th>
                    <th scope="col">CPU</th>
                    <th scope="col">GPU</th>
                    <th scope="col">RAM, ГБ</th>
                    <th scope="col">Ціна за годину, грн</th>
                    <th scope="col">Статус</th>
                    <th scope="col">Дії</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($computers as $computer)
                    <tr>
                        <td>{{ $computer->name }}</td>
                        <td>{{ $computer->cpu }}</td>
                        <td>{{ $computer->gpu }}</td>
                        <td>{{ $computer->ram_gb }}</td>
                        <td class="computer-price">{{ number_format((float) $computer->hourly_rate, 2, ',', ' ') }}</td>
                        <td><span class="computer-status computer-status-{{ $computer->status }}">{{ \App\Models\Computer::STATUSES[$computer->status] ?? $computer->status }}</span></td>
                        <td>
                            <div class="computer-row-actions">
                                <a href="{{ route('computers.show', $computer) }}">Переглянути</a>
                                <a href="{{ route('computers.edit', $computer) }}">Редагувати</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">За цим фільтром комп'ютерів немає.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $computers->links('computers._pagination') }}
</section>
@endsection

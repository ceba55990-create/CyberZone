@extends('layouts.app')

@section('title', 'CyberZone - Довідник країн')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/country.css') }}">
@endpush

@section('content')
<section class="site-page country-page">
    <div class="country-header">
        <h1>Довідник країн</h1>
        <a class="site-button" href="{{ route('country.create') }}">Додати країну</a>
    </div>

    @if (session('success'))
        <p class="country-success" role="status">{{ session('success') }}</p>
    @endif

    <p>Усього країн: {{ $countries->total() }}</p>

    <div class="country-table-scroll">
        <table class="country-table">
            <thead>
                <tr>
                    <th scope="col">№</th>
                    <th scope="col">Код</th>
                    <th scope="col">Назва</th>
                    <th scope="col">Населення</th>
                    <th scope="col">Дії</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($countries as $country)
                    <tr>
                        <td>{{ $countries->firstItem() + $loop->index }}</td>
                        <td>{{ $country->code }}</td>
                        <td>{{ $country->name }}</td>
                        <td class="country-population">{{ number_format($country->population, 0, ',', ' ') }}</td>
                        <td>
                            <div class="country-row-actions">
                                <a href="{{ route('country.show', $country) }}">Переглянути</a>
                                <a href="{{ route('country.edit', $country) }}">Редагувати</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Країн поки немає.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $countries->links('country._pagination') }}
</section>
@endsection

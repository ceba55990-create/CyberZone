<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountryRequest;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(): View
    {
        $countries = Country::query()->orderBy('name')->paginate(5);

        return view('country.index', compact('countries'));
    }

    public function create(): View
    {
        return view('country.create');
    }

    public function store(CountryRequest $request): RedirectResponse
    {
        Country::create($request->validated());

        return redirect()->route('country.index')->with('success', 'Країну додано.');
    }

    public function show(Country $country): View
    {
        return view('country.show', compact('country'));
    }

    public function edit(Country $country): View
    {
        return view('country.edit', compact('country'));
    }

    public function update(CountryRequest $request, Country $country): RedirectResponse
    {
        $country->update($request->validated());

        return redirect()->route('country.index')->with('success', 'Країну оновлено.');
    }

    public function destroy(Country $country): RedirectResponse
    {
        $country->delete();

        return redirect()->route('country.index')->with('success', 'Країну видалено.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComputerRequest;
use App\Models\Computer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComputerController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', '');

        if (! is_string($status) || ! array_key_exists($status, Computer::STATUSES)) {
            $status = '';
        }

        $computers = Computer::query()
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(5)
            ->appends($status !== '' ? ['status' => $status] : []);

        return view('computers.index', compact('computers', 'status'));
    }

    public function create(): View
    {
        return view('computers.create');
    }

    public function store(ComputerRequest $request): RedirectResponse
    {
        Computer::create($request->validated());

        return redirect()->route('computers.index')->with('success', 'ПК додано.');
    }

    public function show(Computer $computer): View
    {
        return view('computers.show', compact('computer'));
    }

    public function edit(Computer $computer): View
    {
        return view('computers.edit', compact('computer'));
    }

    public function update(ComputerRequest $request, Computer $computer): RedirectResponse
    {
        $computer->update($request->validated());

        return redirect()->route('computers.index')->with('success', 'Дані ПК оновлено.');
    }

    public function destroy(Computer $computer): RedirectResponse
    {
        $computer->delete();

        return redirect()->route('computers.index')->with('success', 'ПК видалено.');
    }
}

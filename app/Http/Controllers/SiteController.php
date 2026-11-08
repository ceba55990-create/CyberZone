<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntryRequest;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function say(string $message = 'Привіт'): View
    {
        return view('site.say', ['message' => $message]);
    }

    public function entry(): View
    {
        return view('site.entry');
    }

    public function entryStore(EntryRequest $request): View
    {
        return view('site.entry-confirm', $request->validated());
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;

class ContactController extends Controller
{
    public function index()
    {
        $clubName = 'CyberZone';

        return view('contact', [
            'clubName' => $clubName
        ]);
    }

    public function store(ContactFormRequest $request)
    {
        $validated = $request->validated();

        return view('contact-success', [
            'name' => $validated['name']
        ]);
    }
}

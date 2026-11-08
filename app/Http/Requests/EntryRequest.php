<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле «Ім\'я» не може бути порожнім.',
            'name.string' => 'Ім\'я має бути рядком.',
            'name.max' => 'Ім\'я не може містити більше 255 символів.',
            'email.required' => 'Поле «Email» не може бути порожнім.',
            'email.email' => 'Email введено некоректно.',
        ];
    }
}

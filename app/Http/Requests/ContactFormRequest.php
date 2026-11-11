<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'visit_date' => ['nullable', 'date_format:Y-m-d'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Вкажіть ваше ім'я.",
            'email.required' => 'Вкажіть електронну пошту.',
            'email.email' => 'Введіть коректну електронну адресу.',
            'visit_date.date_format' => 'Вкажіть коректну дату відвідування у форматі РРРР-ММ-ДД.',
            'message.required' => 'Введіть текст повідомлення.',
        ];
    }
}

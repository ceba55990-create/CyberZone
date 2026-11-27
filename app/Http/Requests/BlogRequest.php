<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'text' => ['nullable', 'string'],
            'category_id' => ['bail', 'nullable', 'integer', 'exists:category,category_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Вкажіть заголовок новини.',
            'title.string' => 'Заголовок має бути рядком.',
            'title.max' => 'Заголовок не може містити більше 200 символів.',
            'description.string' => 'Короткий опис має бути рядком.',
            'description.max' => 'Короткий опис не може містити більше 500 символів.',
            'text.string' => 'Текст новини має бути рядком.',
            'category_id.integer' => 'Оберіть коректну категорію.',
            'category_id.exists' => 'Обрана категорія не існує.',
        ];
    }
}

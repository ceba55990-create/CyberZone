<?php

namespace App\Http\Requests;

use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uniqueCode = Rule::unique('country', 'code');
        $country = $this->route('country');

        if ($country instanceof Country) {
            $uniqueCode->ignore($country);
        }

        return [
            'code' => ['bail', 'required', 'string', 'size:2', 'regex:/\A[A-Z]{2}\z/', $uniqueCode],
            'name' => ['bail', 'required', 'string', 'max:52'],
            'population' => ['bail', 'required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Вкажіть код країни.',
            'code.string' => 'Код країни має бути рядком.',
            'code.size' => 'Код країни має містити 2 символи.',
            'code.regex' => 'Код країни має містити рівно дві великі латинські літери A-Z.',
            'code.unique' => 'Країна з таким кодом уже існує.',
            'name.required' => 'Вкажіть назву країни.',
            'name.string' => 'Назва країни має бути рядком.',
            'name.max' => 'Назва країни не може містити більше 52 символів.',
            'population.required' => 'Вкажіть населення країни.',
            'population.integer' => 'Населення має бути цілим числом.',
            'population.min' => 'Населення не може бути від\'ємним.',
            'population.max' => 'Населення не може перевищувати 2147483647.',
        ];
    }
}

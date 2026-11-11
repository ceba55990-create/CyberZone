<?php

namespace App\Http\Requests;

use App\Models\Computer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComputerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uniqueName = Rule::unique('computers', 'name');
        $computer = $this->route('computer');

        if ($computer instanceof Computer) {
            $uniqueName->ignore($computer);
        }

        return [
            'name' => ['bail', 'required', 'string', 'max:100', $uniqueName],
            'cpu' => ['bail', 'required', 'string', 'max:150'],
            'gpu' => ['bail', 'required', 'string', 'max:150'],
            'ram_gb' => ['bail', 'required', 'integer', 'min:1', 'max:1024'],
            'storage_gb' => ['bail', 'required', 'integer', 'min:1', 'max:100000'],
            'hourly_rate' => ['bail', 'required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'status' => ['bail', 'required', 'string', Rule::in(array_keys(Computer::STATUSES))],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле ":attribute" є обов\'язковим.',
            'string' => 'Поле ":attribute" має бути рядком.',
            'integer' => 'Поле ":attribute" має бути цілим числом.',
            'name.unique' => 'ПК з такою назвою вже існує.',
            'name.max' => 'Назва ПК не може містити більше 100 символів.',
            'cpu.max' => 'Назва процесора не може містити більше 150 символів.',
            'gpu.max' => 'Назва відеокарти не може містити більше 150 символів.',
            'ram_gb.min' => 'Обсяг RAM має бути не менше 1 ГБ.',
            'ram_gb.max' => 'Обсяг RAM не може перевищувати 1024 ГБ.',
            'storage_gb.min' => 'Місткість накопичувача має бути не менше 1 ГБ.',
            'storage_gb.max' => 'Місткість накопичувача не може перевищувати 100000 ГБ.',
            'hourly_rate.numeric' => 'Ціна за годину має бути числом.',
            'hourly_rate.min' => 'Ціна за годину не може бути від\'ємною.',
            'hourly_rate.max' => 'Ціна за годину не може перевищувати 999999.99 грн.',
            'hourly_rate.decimal' => 'Ціна за годину може мати не більше двох знаків після крапки.',
            'status.in' => 'Оберіть коректний статус ПК.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Назва ПК',
            'cpu' => 'Процесор',
            'gpu' => 'Відеокарта',
            'ram_gb' => 'RAM у ГБ',
            'storage_gb' => 'Накопичувач у ГБ',
            'hourly_rate' => 'Ціна за годину',
            'status' => 'Статус',
        ];
    }
}

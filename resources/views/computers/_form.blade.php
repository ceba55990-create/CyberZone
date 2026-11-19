<div class="computer-fields">
    @foreach ([
        'name' => ['label' => 'Назва ПК', 'type' => 'text', 'attributes' => ['maxlength' => 100]],
        'cpu' => ['label' => 'Процесор', 'type' => 'text', 'attributes' => ['maxlength' => 150]],
        'gpu' => ['label' => 'Відеокарта', 'type' => 'text', 'attributes' => ['maxlength' => 150]],
        'ram_gb' => ['label' => 'RAM, ГБ', 'type' => 'number', 'attributes' => ['min' => 1, 'max' => 1024, 'step' => 1]],
        'storage_gb' => ['label' => 'Накопичувач, ГБ', 'type' => 'number', 'attributes' => ['min' => 1, 'max' => 100000, 'step' => 1]],
        'hourly_rate' => ['label' => 'Ціна за годину, грн', 'type' => 'number', 'attributes' => ['min' => 0, 'max' => '999999.99', 'step' => '0.01']],
    ] as $field => $settings)
        @php($value = old($field, $computer->{$field} ?? ''))
        <div class="entry-field {{ $field === 'name' ? 'computer-field-wide' : '' }}">
            <label for="{{ $field }}">{{ $settings['label'] }}</label>
            <input id="{{ $field }}" name="{{ $field }}" type="{{ $settings['type'] }}" required
                   value="{{ is_scalar($value) ? $value : '' }}"
                   @foreach ($settings['attributes'] as $attribute => $attributeValue) {{ $attribute }}="{{ $attributeValue }}" @endforeach
                   aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}"
                   @error($field) aria-describedby="{{ $field }}-error" @enderror>
            @error($field)
                <p class="entry-error" id="{{ $field }}-error">{{ $message }}</p>
            @enderror
        </div>
    @endforeach

    @php($selectedStatus = old('status', $computer->status ?? 'available'))
    <div class="entry-field">
        <label for="status">Статус</label>
        <select id="status" name="status" required
                aria-invalid="{{ $errors->has('status') ? 'true' : 'false' }}"
                @error('status') aria-describedby="status-error" @enderror>
            <option value="">Оберіть статус</option>
            @foreach (\App\Models\Computer::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(is_string($selectedStatus) && $selectedStatus === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="entry-error" id="status-error">{{ $message }}</p>
        @enderror
    </div>
</div>

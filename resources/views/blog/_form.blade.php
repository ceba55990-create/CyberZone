@foreach ([
    'title' => ['label' => 'Заголовок', 'max' => 200],
    'description' => ['label' => 'Короткий опис', 'max' => 500],
    'text' => ['label' => 'Текст новини', 'max' => null],
] as $field => $settings)
    @php($value = old($field, $blog->{$field} ?? ''))
    <div>
        <label for="{{ $field }}">{{ $settings['label'] }}</label>
        @if ($field === 'title')
            <input id="{{ $field }}" name="{{ $field }}" type="text" required maxlength="{{ $settings['max'] }}"
                   value="{{ is_scalar($value) ? $value : '' }}"
                   aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}"
                   @error($field) aria-describedby="{{ $field }}-error" @enderror>
        @else
            <textarea id="{{ $field }}" name="{{ $field }}" rows="{{ $field === 'text' ? 10 : 3 }}"
                      @if ($settings['max']) maxlength="{{ $settings['max'] }}" @endif
                      aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}"
                      @error($field) aria-describedby="{{ $field }}-error" @enderror>{{ is_scalar($value) ? $value : '' }}</textarea>
        @endif
        @error($field)
            <p id="{{ $field }}-error" role="alert">{{ $message }}</p>
        @enderror
    </div>
@endforeach

@php($selectedCategory = old('category_id', $blog->category_id ?? ''))
<div>
    <label for="category_id">Категорія</label>
    <select id="category_id" name="category_id"
            aria-invalid="{{ $errors->has('category_id') ? 'true' : 'false' }}"
            @error('category_id') aria-describedby="category_id-error" @enderror>
        <option value="" @selected($selectedCategory === null || $selectedCategory === '')>Без категорії</option>
        @foreach ($categories as $category)
            <option value="{{ $category->getKey() }}" @selected(is_scalar($selectedCategory) && (string) $selectedCategory === (string) $category->getKey())>{{ $category->category_name }}</option>
        @endforeach
    </select>
    @error('category_id')
        <p id="category_id-error" role="alert">{{ $message }}</p>
    @enderror
</div>

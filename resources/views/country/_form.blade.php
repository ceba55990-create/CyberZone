@php
    $code = old('code', $country->code ?? '');
    $name = old('name', $country->name ?? '');
    $population = old('population', $country->population ?? 0);
@endphp

<div class="entry-field">
    <label for="code">Код країни</label>
    <input id="code" name="code" type="text" maxlength="2" required
           value="{{ is_scalar($code) ? $code : '' }}"
           aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}"
           @error('code') aria-describedby="code-error" @enderror>
    @error('code')
        <p class="entry-error" id="code-error">{{ $message }}</p>
    @enderror
</div>

<div class="entry-field">
    <label for="name">Назва країни</label>
    <input id="name" name="name" type="text" maxlength="52" required
           value="{{ is_scalar($name) ? $name : '' }}"
           aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
           @error('name') aria-describedby="name-error" @enderror>
    @error('name')
        <p class="entry-error" id="name-error">{{ $message }}</p>
    @enderror
</div>

<div class="entry-field">
    <label for="population">Населення</label>
    <input id="population" name="population" type="number" min="0" max="2147483647" step="1" required
           value="{{ is_scalar($population) ? $population : '' }}"
           aria-invalid="{{ $errors->has('population') ? 'true' : 'false' }}"
           @error('population') aria-describedby="population-error" @enderror>
    @error('population')
        <p class="entry-error" id="population-error">{{ $message }}</p>
    @enderror
</div>

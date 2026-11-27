<p class="blog-language-switch" role="group" aria-label="Мова новин">
    @foreach (['uk' => 'Українська', 'en' => 'English'] as $locale => $label)
        <a href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}" hreflang="{{ $locale }}" lang="{{ $locale }}"
           @if ($lang === $locale) aria-current="true" @endif>
            @if ($lang === $locale)<strong>{{ $label }}</strong>@else{{ $label }}@endif
        </a>@if (! $loop->last) / @endif
    @endforeach
</p>

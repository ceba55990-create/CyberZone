@php($content = $blog->localizedContent($lang))
<article class="blog-card">
    <img class="blog-card-image" src="{{ asset('images/blog-placeholder.jpg') }}"
         alt="Ігрове місце в комп’ютерному клубі CyberZone"
         width="1672" height="941" loading="lazy" decoding="async">
    <p class="blog-card-category">Категорія: {{ $blog->category?->category_name ?? 'Без категорії' }}</p>
    <h2><a href="{{ route('blog.show', ['blog' => $blog] + $queryParams) }}">{{ $content['title'] }}</a></h2>
    <p class="blog-card-meta">Автор: {{ $blog->author?->name ?? 'Не вказано' }}</p>
    <p class="blog-card-meta">Дата публікації: <time datetime="{{ $blog->dt_created->toIso8601String() }}">{{ $blog->dt_created->format('d.m.Y H:i') }}</time></p>
    @if ($content['description'] !== null && $content['description'] !== '')
        <p class="blog-card-description">{{ \Illuminate\Support\Str::limit($content['description'], 180) }}</p>
    @endif
    <a class="blog-read-more" href="{{ route('blog.show', ['blog' => $blog] + $queryParams) }}" aria-label="Читати далі: {{ $content['title'] }}">Читати далі <span aria-hidden="true">→</span></a>
</article>

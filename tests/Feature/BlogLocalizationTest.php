<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BlogLocalizationTest extends TestCase
{
    private Blog $blog;

    protected function setUp(): void
    {
        parent::setUp();

        // The parent verifies SQLite :memory: before any migration or fixture writes.
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_11_27_000000_create_category_table.php',
            '2026_11_27_000001_create_blog_table.php',
            '2026_11_27_000002_create_blog_translations_table.php',
        ] as $migrationFile) {
            (require database_path('migrations/'.$migrationFile))->up();
        }

        $category = Category::create(['category_name' => 'Технології']);
        $author = User::factory()->create(['name' => 'Автор CyberZone']);
        $this->blog = Blog::create([
            'title' => 'Базовий український заголовок',
            'description' => 'Базовий український опис новини.',
            'text' => 'Базовий повний український текст новини.',
            'category_id' => $category->getKey(),
            'user_id' => $author->getKey(),
        ]);
        foreach (['uk' => $this->ukrainian(), 'en' => $this->english()] as $locale => $content) {
            $this->blog->translations()->create(['locale' => $locale] + $content);
        }
    }

    #[DataProvider('languagePages')]
    public function test_pages_select_default_ukrainian_or_the_requested_translation(string $route, ?string $lang): void
    {
        $before = $this->blog->fresh()->getAttributes();
        $appLocale = app()->getLocale();
        $expected = $lang === 'en' ? $this->english() : $this->blog->only(['title', 'description', 'text']);
        $unexpectedTitle = $lang === 'en' ? $before['title'] : $this->ukrainian()['title'];
        $response = $this->get($this->url($route, $lang === null ? [] : ['lang' => $lang]))
            ->assertOk()->assertSee($expected['title'])->assertDontSee($unexpectedTitle);

        if ($route !== 'blog.admin') {
            $response->assertSee($expected['description']);
        }
        if ($route === 'blog.show') {
            $response->assertSee($expected['text']);
            $this->assertSame('CyberZone - '.$expected['title'], $this->document($response->getContent())->evaluate('string(//title)'));
        }

        $record = $route === 'blog.show' ? $response->viewData('blog') : $response->viewData('blogs')->first();
        $this->assertSame($before, $record->getAttributes());
        $this->assertFalse($record->isDirty());
        $this->assertSame($before, $this->blog->fresh()->getAttributes());
        $this->assertSame($appLocale, app()->getLocale());
        $this->assertSame($lang ?? 'uk', $response->viewData('lang'));
        $this->assertDatabaseCount('blog', 1);
        $this->assertDatabaseCount('blog_translations', 2);
    }

    public static function languagePages(): array
    {
        $cases = [];
        foreach (['blog.index', 'blog.show', 'blog.admin'] as $route) {
            foreach ([null, 'uk', 'en'] as $lang) {
                $cases[$route.' '.($lang ?? 'default')] = [$route, $lang];
            }
        }

        return $cases;
    }

    public function test_crud_updates_are_visible_in_ukrainian_while_stored_translations_are_preserved(): void
    {
        $ukrainian = $this->blog->translations()->where('locale', 'uk')->firstOrFail();
        $english = $this->blog->translations()->where('locale', 'en')->firstOrFail();
        $beforeUkrainian = $ukrainian->getAttributes();
        $beforeEnglish = $english->getAttributes();
        $updated = [
            'title' => 'Оновлений український заголовок після редагування',
            'description' => 'Оновлений український опис після редагування через CRUD.',
            'text' => 'Оновлений повний український текст після редагування через CRUD.',
        ];

        $this->put(route('blog.update', $this->blog), $updated)
            ->assertRedirectToRoute('blog.show', $this->blog)->assertSessionHasNoErrors();

        foreach (['blog.index', 'blog.show', 'blog.admin'] as $route) {
            foreach ([[], ['lang' => 'uk']] as $query) {
                $response = $this->get($this->url($route, $query))->assertOk()
                    ->assertSee($updated['title'])->assertDontSee($ukrainian->title);
                if ($route !== 'blog.admin') {
                    $response->assertSee($updated['description'])->assertDontSee($ukrainian->description);
                }
                if ($route === 'blog.show') {
                    $response->assertSee($updated['text'])->assertDontSee($ukrainian->text);
                }
            }

            $this->get($this->url($route, ['lang' => 'en']))->assertOk()
                ->assertSee($english->title)->assertDontSee($updated['title']);
        }

        $this->assertSame($updated, $this->blog->fresh('translations')->localizedContent('uk'));
        $this->assertSame($beforeUkrainian, $ukrainian->fresh()->getAttributes());
        $this->assertSame($beforeEnglish, $english->fresh()->getAttributes());
        $this->assertDatabaseCount('blog_translations', 2);
    }

    #[DataProvider('missingTranslations')]
    public function test_missing_or_unknown_localization_uses_the_base_ukrainian_fields(string $route, string $lang): void
    {
        $this->blog->translations()->where('locale', $lang)->delete();
        $response = $this->get($this->url($route, ['lang' => $lang]))->assertOk()
            ->assertSee($this->blog->title)->assertDontSee($this->english()['title']);

        if ($route !== 'blog.admin') {
            $response->assertSee($this->blog->description);
        }
        if ($route === 'blog.show') {
            $response->assertSee($this->blog->text);
        }
    }

    public static function missingTranslations(): array
    {
        $cases = [];
        foreach (['blog.index', 'blog.show', 'blog.admin'] as $route) {
            foreach (['uk', 'en', 'fr', 'unknown'] as $lang) {
                $cases[$route.' '.$lang] = [$route, $lang];
            }
        }

        return $cases;
    }

    #[DataProvider('emptyFields')]
    public function test_optional_fields_fall_back_individually(?string $description, ?string $text): void
    {
        $this->blog->translations()->where('locale', 'en')->update(compact('description', 'text'));
        $expectedDescription = $description === null || trim($description) === '' ? $this->blog->description : $description;
        $expectedText = $text === null || trim($text) === '' ? $this->blog->text : $text;

        $this->get($this->url('blog.show', ['lang' => 'en']))->assertOk()
            ->assertSee($this->english()['title'])->assertSee($expectedDescription)->assertSee($expectedText);
        $this->get($this->url('blog.index', ['lang' => 'en']))->assertOk()->assertSee($expectedDescription);
        $this->assertSame([
            'title' => $this->english()['title'], 'description' => $expectedDescription, 'text' => $expectedText,
        ], $this->blog->fresh()->localizedContent('en'));
    }

    public static function emptyFields(): array
    {
        return [
            'null fields' => [null, null],
            'empty strings' => ['', ''],
            'whitespace' => ['   ', "\n\t"],
            'only description absent' => [null, 'Translated full text remains available.'],
            'only text absent' => ['Translated description remains available.', null],
            'zero is valid text' => ['0', '0'],
        ];
    }

    public function test_empty_translated_title_also_falls_back_without_discarding_other_fields(): void
    {
        $this->blog->translations()->where('locale', 'en')->update(['title' => '   ']);
        $this->get($this->url('blog.show', ['lang' => 'en']))->assertOk()
            ->assertSee($this->blog->title)->assertSee($this->english()['description'])->assertSee($this->english()['text']);
    }

    #[DataProvider('invalidLanguages')]
    public function test_invalid_lang_values_are_safe_on_all_read_pages(mixed $lang): void
    {
        foreach (['blog.index', 'blog.show', 'blog.admin'] as $route) {
            $this->get($this->url($route, ['lang' => $lang]))->assertOk()
                ->assertViewHas('lang', 'uk')->assertSee($this->blog->title);
        }
    }

    public static function invalidLanguages(): array
    {
        return [
            'array' => [['en']],
            'nested array' => [['value' => ['en']]],
            'empty' => [''],
            'number' => ['123'],
            'too long' => [str_repeat('en', 20)],
            'HTML' => ['<script>en</script>'],
        ];
    }

    #[DataProvider('readPages')]
    public function test_a_third_language_requires_only_a_translation_record(string $route): void
    {
        $this->blog->translations()->create([
            'locale' => 'pl', 'title' => 'Polskie aktualności CyberZone',
            'description' => 'Polski opis wiadomości.', 'text' => 'Pełny polski tekst wiadomości.',
        ]);

        $this->get($this->url($route, ['lang' => 'pl']))->assertOk()->assertSee('Polskie aktualności CyberZone');
        $this->assertDatabaseCount('blog', 1);
    }

    #[DataProvider('readPages')]
    public function test_language_switch_preserves_current_path_page_and_query_parameters(string $route): void
    {
        $this->createMoreBlogs(11);
        $query = ['lang' => 'en', 'page' => 2, 'source' => 'club', 'topics' => ['pc', 'games']];
        $response = $this->get($this->url($route, $query))->assertOk();
        $xpath = $this->document($response->getContent());

        foreach (['uk', 'en'] as $locale) {
            $href = $xpath->evaluate('string(//p[@class="blog-language-switch"]/a[@hreflang="'.$locale.'"]/@href)');
            parse_str(parse_url($href, PHP_URL_QUERY), $actual);
            $this->assertSame(parse_url($this->url($route), PHP_URL_PATH), parse_url($href, PHP_URL_PATH));
            $this->assertSame($locale, $actual['lang']);
            $this->assertSame('2', $actual['page']);
            $this->assertSame('club', $actual['source']);
            $this->assertSame($query['topics'], $actual['topics']);
        }
        $this->assertSame('en', $xpath->evaluate('string(//p[@class="blog-language-switch"]/a[@aria-current="true"]/@hreflang)'));
    }

    #[DataProvider('listPages')]
    public function test_pagination_and_news_links_keep_the_language_and_list_context(string $route, int $perPage): void
    {
        $this->createMoreBlogs(11);
        $query = ['lang' => 'en', 'page' => 2, 'source' => 'club'];
        $response = $this->get($this->url($route, $query))->assertOk();
        $paginator = $response->viewData('blogs');
        $this->assertSame(12, $paginator->total());
        $this->assertSame($perPage, $paginator->perPage());
        $this->assertSame($paginator->count(), $paginator->getCollection()->unique('blog_id')->count());

        $xpath = $this->document($response->getContent());
        $previous = $xpath->evaluate('string(//nav[@class="blog-pagination"]//a[@rel="prev"]/@href)');
        parse_str(parse_url($previous, PHP_URL_QUERY), $previousQuery);
        $this->assertSame('en', $previousQuery['lang']);
        $this->assertSame('1', $previousQuery['page']);
        $this->assertSame('club', $previousQuery['source']);

        $newsUrl = $route === 'blog.index'
            ? $xpath->evaluate('string(//article[@class="blog-card"]/h2/a/@href)')
            : $xpath->evaluate('string(//table[@class="blog-table"]//a[normalize-space()="Переглянути"]/@href)');
        parse_str(parse_url($newsUrl, PHP_URL_QUERY), $newsQuery);
        $this->assertSame('en', $newsQuery['lang']);
        $this->assertSame('2', $newsQuery['page']);

        $detail = $this->get($newsUrl)->assertOk();
        $back = $this->document($detail->getContent())->evaluate('string(//a[normalize-space()="До списку новин"]/@href)');
        parse_str(parse_url($back, PHP_URL_QUERY), $backQuery);
        $this->assertSame($newsQuery, $backQuery);
        $this->get($back)->assertOk()->assertViewIs('blog.index')->assertViewHas('lang', 'en');

        $this->assertDatabaseCount('blog', 12);
        $this->assertDatabaseCount('blog_translations', 24);
    }

    #[DataProvider('listPages')]
    public function test_translation_query_count_is_constant_as_the_list_grows(string $route, int $perPage): void
    {
        [$smallResponse, $smallQueries] = $this->loggedList($route);
        $this->createMoreBlogs($perPage - 1);
        [$largeResponse, $largeQueries] = $this->loggedList($route);

        $this->assertCount(count($smallQueries), $largeQueries);
        foreach ([$smallQueries, $largeQueries] as $queries) {
            $translationQueries = array_values(array_filter($queries, fn (array $query) => str_contains($query['query'], '"blog_translations"')));
            $this->assertCount(1, $translationQueries);
            $this->assertContains('en', $translationQueries[0]['bindings']);
        }
        $this->assertCount(1, $smallResponse->viewData('blogs'));
        $this->assertCount($perPage, $largeResponse->viewData('blogs'));
        foreach ($largeResponse->viewData('blogs') as $record) {
            $this->assertTrue($record->relationLoaded('translations'));
            $this->assertCount(1, $record->translations);
            $this->assertSame('en', $record->translations->first()->locale);
        }
    }

    public function test_forms_keep_base_fields_even_when_lang_is_english(): void
    {
        $this->get(route('blog.edit', ['blog' => $this->blog, 'lang' => 'en']))->assertOk()
            ->assertSee('value="'.$this->blog->title.'"', false)
            ->assertSee($this->blog->description)->assertSee($this->blog->text)
            ->assertDontSee($this->english()['title']);
        $this->get(route('blog.create', ['lang' => 'en']))->assertOk()->assertSee('Створити новину');
    }

    #[DataProvider('readPages')]
    public function test_translated_content_remains_escaped(string $route): void
    {
        $payload = '<script>alert("translation")</script>';
        $this->blog->translations()->where('locale', 'en')->update([
            'title' => $payload, 'description' => $payload, 'text' => $payload,
        ]);
        $this->get($this->url($route, ['lang' => 'en']))->assertOk()
            ->assertSee($payload)->assertDontSee($payload, false);
    }

    public static function readPages(): array
    {
        return [['blog.index'], ['blog.show'], ['blog.admin']];
    }

    public static function listPages(): array
    {
        return [['blog.index', 6], ['blog.admin', 10]];
    }

    private function loggedList(string $route): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $response = $this->get($this->url($route, ['lang' => 'en']))->assertOk();
            $queries = array_values(array_filter(DB::getQueryLog(), fn (array $query) => str_starts_with(strtolower($query['query']), 'select')));
        } finally {
            DB::disableQueryLog();
        }

        return [$response, $queries];
    }

    private function createMoreBlogs(int $count): void
    {
        for ($number = 1; $number <= $count; $number++) {
            $blog = Blog::create([
                'title' => 'Базовий заголовок '.$number,
                'category_id' => $this->blog->category_id, 'user_id' => $this->blog->user_id,
            ]);
            foreach (['uk' => $this->ukrainian(), 'en' => $this->english()] as $locale => $content) {
                $content['title'] .= ' '.$number;
                $blog->translations()->create(['locale' => $locale] + $content);
            }
        }
    }

    private function url(string $route, array $query = []): string
    {
        return route($route, $route === 'blog.show' ? ['blog' => $this->blog] + $query : $query);
    }

    private function ukrainian(): array
    {
        return [
            'title' => 'Український переклад заголовка',
            'description' => 'Український переклад короткого опису.',
            'text' => 'Український переклад повного тексту новини.',
        ];
    }

    private function english(): array
    {
        return [
            'title' => 'English news title',
            'description' => 'English news description.',
            'text' => 'English full news text.',
        ];
    }

    private function document(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
        }

        return new DOMXPath($document);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BlogCrudTest extends TestCase
{
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests\TestCase guards the connection as SQLite :memory: before these migrations run.
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_11_27_000000_create_category_table.php',
            '2026_11_27_000001_create_blog_table.php',
            '2026_11_27_000002_create_blog_translations_table.php',
        ] as $migrationFile) {
            (require database_path('migrations/'.$migrationFile))->up();
        }

        $this->category = Category::create(['category_name' => 'Новини']);
    }

    public function test_empty_index_and_create_form_use_the_shared_layout(): void
    {
        $this->get(route('blog.index'))->assertOk()->assertViewIs('blog.index')
            ->assertSee('Новин поки немає.')
            ->assertSee('href="'.route('blog.create').'"', false)
            ->assertSee('Made by Vsevolod Yaremich');

        $response = $this->get(route('blog.create'))->assertOk()->assertViewIs('blog.create')
            ->assertSee('method="POST" action="'.route('blog.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Новини')->assertSee('Без категорії')
            ->assertSee('Made by Vsevolod Yaremich');

        $this->assertSame(1, $this->document($response->getContent())->query('//footer')->length);
    }

    public function test_index_lists_news_with_links_in_reverse_publication_order(): void
    {
        $older = Blog::create($this->validData(['title' => 'Перша новина']));
        $newer = Blog::create($this->validData(['title' => 'Друга новина']));

        $this->get(route('blog.index'))->assertOk()
            ->assertSeeInOrder(['Друга новина', 'Перша новина'])
            ->assertSee('href="'.route('blog.show', $older).'"', false)
            ->assertSee('href="'.route('blog.show', $newer).'"', false);
    }

    public function test_public_list_paginates_six_cards_and_keeps_the_remaining_two_on_page_two(): void
    {
        $blogs = array_reverse($this->createBlogs(8));
        $first = $this->get(route('blog.index'))->assertOk()->assertViewIs('blog.index')
            ->assertViewHas('blogs', fn (LengthAwarePaginator $paginator) => $paginator->total() === 8 && $paginator->perPage() === 6)
            ->assertSee('href="'.route('blog.create').'"', false)
            ->assertSee('href="'.route('blog.admin').'"', false)
            ->assertDontSee($blogs[0]->text)
            ->assertSee('aria-current="page"', false);

        $second = $this->get(route('blog.index', ['page' => 2]))->assertOk();

        foreach ([[$first, array_slice($blogs, 0, 6)], [$second, array_slice($blogs, 6)]] as [$response, $expected]) {
            $this->assertSame(
                array_map(fn (Blog $blog) => $blog->title, $expected),
                $this->nodeTexts($response->getContent(), '//article[@class="blog-card"]/h2/a'),
            );
            $this->assertSame(
                array_map(fn (Blog $blog) => $blog->getKey(), $expected),
                $response->viewData('blogs')->getCollection()->modelKeys(),
            );
        }

        $this->assertSame(route('blog.index', ['page' => 2]), $this->document($first->getContent())->evaluate('string(//nav[@class="blog-pagination"]//a[@rel="next"]/@href)'));
        $this->assertSame('', $this->document($second->getContent())->evaluate('string(//nav[@class="blog-pagination"]//a[@rel="next"]/@href)'));
    }

    public function test_public_cards_use_a_real_local_jpeg_and_link_to_the_full_news(): void
    {
        $blog = Blog::create($this->validData());
        $response = $this->get(route('blog.index'))->assertOk();
        $xpath = $this->document($response->getContent());

        $imagePath = public_path('images/blog-placeholder.jpg');
        $this->assertFileExists($imagePath);
        $image = getimagesize($imagePath);
        $this->assertIsArray($image);
        $this->assertSame('image/jpeg', $image['mime']);
        $this->assertGreaterThan(0, $image[0]);
        $this->assertGreaterThan(0, $image[1]);
        $this->assertSame(asset('images/blog-placeholder.jpg'), $xpath->evaluate('string(//article[@class="blog-card"]/img/@src)'));
        $this->assertNotSame('', $xpath->evaluate('string(//article[@class="blog-card"]/img/@alt)'));
        $this->assertSame(route('blog.show', $blog), $xpath->evaluate('string(//article[@class="blog-card"]/a[@class="blog-read-more"]/@href)'));
        $this->assertStringContainsString('Читати далі', $xpath->evaluate('string(//article[@class="blog-card"]/a[@class="blog-read-more"])'));
    }

    public function test_cards_shorten_long_ukrainian_descriptions_without_changing_the_record(): void
    {
        $description = str_repeat('Новини ігрового клубу CyberZone. ', 15);
        $blog = Blog::create($this->validData(['description' => $description]));
        $response = $this->get(route('blog.index'))->assertOk();
        $excerpt = $this->document($response->getContent())->evaluate('string(//article[@class="blog-card"]/p[@class="blog-card-description"])');

        $this->assertLessThanOrEqual(183, mb_strlen($excerpt));
        $this->assertStringStartsWith('Новини ігрового клубу CyberZone.', $excerpt);
        $this->assertStringEndsWith('...', $excerpt);
        $response->assertDontSee($description);
        $this->assertSame($description, $blog->fresh()->description);
        $this->get(route('blog.show', $blog))->assertOk()->assertSee($description);
    }

    public function test_admin_paginates_ten_rows_with_continuous_numbering(): void
    {
        $blogs = array_reverse($this->createBlogs(12));
        $first = $this->get(route('blog.admin'))->assertOk()->assertViewIs('blog.admin')
            ->assertViewHas('blogs', fn (LengthAwarePaginator $paginator) => $paginator->total() === 12 && $paginator->perPage() === 10);
        $second = $this->get(route('blog.admin', ['page' => 2]))->assertOk();

        foreach ([[$first, array_slice($blogs, 0, 10), range(1, 10)], [$second, array_slice($blogs, 10), [11, 12]]] as [$response, $expected, $numbers]) {
            $this->assertSame(
                array_map(fn (Blog $blog) => $blog->title, $expected),
                $this->nodeTexts($response->getContent(), '//table[@class="blog-table"]/tbody/tr/td[2]'),
            );
            $this->assertSame(
                array_map('strval', $numbers),
                $this->nodeTexts($response->getContent(), '//table[@class="blog-table"]/tbody/tr/td[1]'),
            );
        }
    }

    #[DataProvider('listRoutes')]
    public function test_lists_sort_by_creation_date_then_id_descending(string $route): void
    {
        $blogs = $this->createBlogs(4);
        foreach (['2026-11-20 12:00:00', '2026-11-01 12:00:00', '2026-11-10 12:00:00', '2026-11-10 12:00:00'] as $index => $date) {
            $blogs[$index]->forceFill(['dt_created' => $date])->save();
        }
        $expected = [$blogs[0], $blogs[3], $blogs[2], $blogs[1]];

        $response = $this->get(route($route))->assertOk()
            ->assertSeeInOrder(array_map(fn (Blog $blog) => $blog->title, $expected));

        $this->assertSame(
            array_map(fn (Blog $blog) => $blog->getKey(), $expected),
            $response->viewData('blogs')->getCollection()->modelKeys(),
        );
    }

    #[DataProvider('listRoutes')]
    public function test_lists_eager_load_relations_and_handle_missing_category_and_author(string $route): void
    {
        $author = User::factory()->create(['name' => 'Автор CyberZone']);
        $blog = Blog::create($this->validData() + ['user_id' => $author->getKey()]);
        $orphan = Blog::create($this->validData(['title' => 'Новина без категорії', 'category_id' => null]));

        $response = $this->get(route($route))->assertOk()->assertSee($blog->title)
            ->assertSee($orphan->title)->assertSee('Новини')->assertSee('Без категорії')
            ->assertSee($blog->dt_created->format('d.m.Y H:i'));

        foreach ($response->viewData('blogs') as $record) {
            $this->assertTrue($record->relationLoaded('category'));
            $this->assertTrue($record->relationLoaded('author'));
        }

        if ($route === 'blog.index') {
            $response->assertSee('Автор: Автор CyberZone')->assertSee('Автор: Не вказано')
                ->assertSee($blog->description);
        }
    }

    #[DataProvider('listRoutes')]
    public function test_pagination_links_preserve_query_parameters(string $route): void
    {
        $this->createBlogs(12);
        $filters = ['source' => 'club', 'topics' => ['pc', 'games']];
        $first = $this->get(route($route, $filters))->assertOk();
        $nextUrl = $this->document($first->getContent())->evaluate('string(//nav[@class="blog-pagination"]//a[@rel="next"]/@href)');
        parse_str(parse_url($nextUrl, PHP_URL_QUERY), $nextQuery);

        $this->assertSame('club', $nextQuery['source']);
        $this->assertSame($filters['topics'], $nextQuery['topics']);
        $this->assertSame('2', $nextQuery['page']);
        $this->assertSame(parse_url(route($route), PHP_URL_PATH), parse_url($nextUrl, PHP_URL_PATH));

        $second = $this->get($nextUrl)->assertOk();
        $previousUrl = $this->document($second->getContent())->evaluate('string(//nav[@class="blog-pagination"]//a[@rel="prev"]/@href)');
        parse_str(parse_url($previousUrl, PHP_URL_QUERY), $previousQuery);

        $this->assertSame('club', $previousQuery['source']);
        $this->assertSame($filters['topics'], $previousQuery['topics']);
        $this->assertSame('1', $previousQuery['page']);
        $this->assertSame('2', $this->document($second->getContent())->evaluate('string(//nav[@class="blog-pagination"]//*[@aria-current="page"])'));
    }

    public function test_admin_route_shows_an_empty_table_and_shared_layout(): void
    {
        $this->assertSame('/blog/admin', parse_url(route('blog.admin'), PHP_URL_PATH));
        $response = $this->get('/blog/admin')->assertOk()->assertViewIs('blog.admin')
            ->assertSee('Новин поки немає.')
            ->assertSee('href="'.route('blog.index').'"', false)
            ->assertSee('href="'.route('blog.create').'"', false)
            ->assertSee('Made by Vsevolod Yaremich');

        $this->assertSame(['№', 'Заголовок', 'Категорія', 'Дата', 'Дії'], $this->nodeTexts($response->getContent(), '//table[@class="blog-table"]/thead/tr/th'));
        $this->assertSame(1, $this->document($response->getContent())->query('//footer')->length);
        $this->assertSame(0, $this->document($response->getContent())->query('//nav[@class="blog-pagination"]')->length);
    }

    public function test_admin_provides_view_edit_and_a_working_delete_form(): void
    {
        $blog = Blog::create($this->validData());
        $response = $this->get(route('blog.admin'))->assertOk()
            ->assertSee('href="'.route('blog.show', $blog).'"', false)
            ->assertSee('href="'.route('blog.edit', $blog).'"', false);
        $xpath = $this->document($response->getContent());
        $action = $xpath->evaluate('string(//table[@class="blog-table"]//form/@action)');
        $this->assertSame(route('blog.destroy', $blog), $action);
        $this->assertSame('POST', $xpath->evaluate('string(//table[@class="blog-table"]//form/@method)'));
        $this->assertSame('DELETE', $xpath->evaluate('string(//table[@class="blog-table"]//form/input[@name="_method"]/@value)'));
        $this->assertNotSame('', $xpath->evaluate('string(//table[@class="blog-table"]//form/input[@name="_token"]/@value)'));

        $this->post($action, ['_method' => 'DELETE'])->assertRedirectToRoute('blog.index');
        $this->assertDatabaseMissing('blog', ['blog_id' => $blog->getKey()]);
        $this->get(route('blog.admin'))->assertOk()->assertSee('Новин поки немає.');
    }

    public static function listRoutes(): array
    {
        return [['blog.index'], ['blog.admin']];
    }

    public function test_store_saves_validated_fields_and_redirects_to_the_created_news(): void
    {
        $data = $this->validData();
        $response = $this->post(route('blog.store'), $data + [
            'blog_id' => 999999,
            'user_id' => 999999,
            'dt_created' => '2000-01-01 00:00:00',
            'dt_updated' => '2000-01-01 00:00:00',
        ])->assertSessionHasNoErrors()->assertSessionHas('success', 'Новину створено.');

        $blog = Blog::where('title', $data['title'])->firstOrFail();
        $response->assertRedirectToRoute('blog.show', $blog);
        $this->assertNotSame(999999, $blog->getKey());
        $this->assertNull($blog->user_id);
        $this->assertNotSame('2000-01-01 00:00:00', $blog->dt_created->toDateTimeString());
        $this->assertDatabaseHas('blog', $data + ['blog_id' => $blog->getKey(), 'user_id' => null]);
        $this->assertDatabaseCount('blog', 1);
        $this->get(route('blog.show', $blog))->assertOk()->assertSee('Новину створено.');
    }

    public function test_store_allows_empty_optional_fields(): void
    {
        $this->post(route('blog.store'), ['title' => 'Коротка новина'])
            ->assertSessionHasNoErrors();

        $blog = Blog::firstOrFail();
        $this->assertDatabaseHas('blog', [
            'blog_id' => $blog->getKey(), 'description' => null, 'text' => null,
            'category_id' => null, 'user_id' => null,
        ]);
        $this->get(route('blog.show', $blog))->assertOk()->assertSee('Без категорії')->assertSee('Не вказано');
    }

    public function test_show_displays_category_date_full_text_and_a_nullable_author(): void
    {
        $blog = Blog::create($this->validData());

        $this->get(route('blog.show', $blog))->assertOk()->assertViewIs('blog.show')
            ->assertViewHas('blog', fn (Blog $record) => $record->getKey() === $blog->getKey())
            ->assertSee($blog->title)->assertSee($blog->description)->assertSee($blog->text)
            ->assertSee('Категорія: Новини')->assertSee('Автор: Не вказано')
            ->assertSee($blog->dt_created->format('d.m.Y H:i'))
            ->assertSee('href="'.route('blog.edit', $blog).'"', false)
            ->assertSee('action="'.route('blog.destroy', $blog).'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('onsubmit="return confirm(', false);

        $author = User::factory()->create(['name' => 'Автор CyberZone']);
        $blog->update(['user_id' => $author->getKey()]);
        $this->get(route('blog.show', $blog))->assertOk()->assertSee('Автор: Автор CyberZone');
    }

    public function test_edit_prefills_fields_and_selects_the_existing_category(): void
    {
        $blog = Blog::create($this->validData());
        $response = $this->get(route('blog.edit', $blog))->assertOk()->assertViewIs('blog.edit')
            ->assertSee('method="POST" action="'.route('blog.update', $blog).'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('Made by Vsevolod Yaremich');

        $xpath = $this->document($response->getContent());
        $this->assertSame($blog->title, $xpath->evaluate('string(//input[@name="title"]/@value)'));
        $this->assertSame($blog->description, $xpath->evaluate('string(//textarea[@name="description"])'));
        $this->assertSame($blog->text, $xpath->evaluate('string(//textarea[@name="text"])'));
        $this->assertSame((string) $this->category->getKey(), $xpath->evaluate('string(//select[@name="category_id"]/option[@selected]/@value)'));
    }

    #[DataProvider('updateMethods')]
    public function test_update_redirects_to_the_news_and_preserves_its_author_and_creation_date(string $method): void
    {
        $author = User::factory()->create();
        $blog = Blog::create($this->validData() + ['user_id' => $author->getKey()]);
        $createdAt = $blog->dt_created->toDateTimeString();
        $updatedAt = $blog->dt_updated->copy()->addHour();
        $category = Category::create(['category_name' => 'Події']);

        $this->travelTo($updatedAt);
        $this->{$method}(route('blog.update', $blog), $this->validData([
            'title' => 'Оновлена новина', 'category_id' => $category->getKey(),
        ]) + ['user_id' => 999999, 'dt_created' => '2000-01-01 00:00:00'])
            ->assertRedirectToRoute('blog.show', $blog)->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Новину оновлено.');
        $this->travelBack();

        $blog->refresh();
        $this->assertSame('Оновлена новина', $blog->title);
        $this->assertSame($category->getKey(), $blog->category_id);
        $this->assertSame($author->getKey(), $blog->user_id);
        $this->assertSame($createdAt, $blog->dt_created->toDateTimeString());
        $this->assertSame($updatedAt->toDateTimeString(), $blog->dt_updated->toDateTimeString());
        $this->assertDatabaseCount('blog', 1);
        $this->get(route('blog.show', $blog))->assertOk()->assertSee('Новину оновлено.');
    }

    public static function updateMethods(): array
    {
        return [['put'], ['patch']];
    }

    public function test_update_can_clear_optional_fields(): void
    {
        $blog = Blog::create($this->validData());
        $this->put(route('blog.update', $blog), [
            'title' => $blog->title, 'description' => '', 'text' => '', 'category_id' => '',
        ])->assertRedirectToRoute('blog.show', $blog)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('blog', [
            'blog_id' => $blog->getKey(), 'description' => null, 'text' => null, 'category_id' => null,
        ]);
    }

    public function test_destroy_removes_only_the_requested_news(): void
    {
        $blog = Blog::create($this->validData());
        $other = Blog::create($this->validData(['title' => 'Збережена новина']));

        $this->delete(route('blog.destroy', $blog))->assertRedirectToRoute('blog.index')
            ->assertSessionHas('success', 'Новину видалено.');

        $this->assertDatabaseMissing('blog', ['blog_id' => $blog->getKey()]);
        $this->assertDatabaseHas('blog', ['blog_id' => $other->getKey()]);
        $this->assertDatabaseCount('blog', 1);
        $this->get(route('blog.index'))->assertOk()->assertSee('Новину видалено.');
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_store_is_rejected_without_creating_news(array $changes, string $field): void
    {
        $this->from(route('blog.create'))->post(route('blog.store'), $this->validData($changes))
            ->assertRedirectToRoute('blog.create')->assertSessionHasErrors($field);

        $this->assertDatabaseCount('blog', 0);
        $this->get(route('blog.create'))->assertOk()->assertSee('id="'.$field.'-error"', false);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_update_leaves_the_existing_news_unchanged(array $changes, string $field): void
    {
        $blog = Blog::create($this->validData());
        $before = $blog->refresh()->getAttributes();

        $this->from(route('blog.edit', $blog))->put(route('blog.update', $blog), $this->validData($changes))
            ->assertRedirectToRoute('blog.edit', $blog)->assertSessionHasErrors($field);

        $this->assertSame($before, $blog->fresh()->getAttributes());
        $this->get(route('blog.edit', $blog))->assertOk()->assertSee('id="'.$field.'-error"', false);
    }

    public static function invalidInputs(): array
    {
        return [
            'missing title' => [['title' => null], 'title'],
            'blank title' => [['title' => '   '], 'title'],
            'long title' => [['title' => str_repeat('Ї', 201)], 'title'],
            'array title' => [['title' => ['Новина']], 'title'],
            'long description' => [['description' => str_repeat('О', 501)], 'description'],
            'array description' => [['description' => ['Опис']], 'description'],
            'array text' => [['text' => ['Текст']], 'text'],
            'nonexistent category' => [['category_id' => 999999], 'category_id'],
            'nonnumeric category' => [['category_id' => 'Новини'], 'category_id'],
            'array category' => [['category_id' => [1]], 'category_id'],
            'fractional category' => [['category_id' => '1.5'], 'category_id'],
        ];
    }

    public function test_validation_accepts_maximum_text_lengths(): void
    {
        $data = $this->validData(['title' => str_repeat('Ї', 200), 'description' => str_repeat('О', 500)]);
        $this->post(route('blog.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('blog', $data);
    }

    public function test_validation_preserves_old_values_and_category_selection(): void
    {
        $data = $this->validData(['title' => 'Новина "ПК" <test>', 'description' => str_repeat('О', 501)]);
        $this->from(route('blog.create'))->post(route('blog.store'), $data)->assertSessionHasErrors('description');
        $response = $this->get(route('blog.create'))->assertOk();
        $xpath = $this->document($response->getContent());

        $this->assertSame($data['title'], $xpath->evaluate('string(//input[@name="title"]/@value)'));
        $this->assertSame($data['description'], $xpath->evaluate('string(//textarea[@name="description"])'));
        $this->assertSame($data['text'], $xpath->evaluate('string(//textarea[@name="text"])'));
        $this->assertSame((string) $this->category->getKey(), $xpath->evaluate('string(//select[@name="category_id"]/option[@selected]/@value)'));
    }

    public function test_failed_edit_keeps_empty_old_values_instead_of_model_values(): void
    {
        $blog = Blog::create($this->validData());
        $this->from(route('blog.edit', $blog))->put(route('blog.update', $blog), [
            'title' => '', 'description' => '', 'text' => '', 'category_id' => '',
        ])->assertSessionHasErrors('title');
        $response = $this->get(route('blog.edit', $blog))->assertOk();
        $xpath = $this->document($response->getContent());

        $this->assertSame('', $xpath->evaluate('string(//input[@name="title"]/@value)'));
        $this->assertSame('', $xpath->evaluate('string(//textarea[@name="description"])'));
        $this->assertSame('', $xpath->evaluate('string(//textarea[@name="text"])'));
        $this->assertSame('', $xpath->evaluate('string(//select[@name="category_id"]/option[@selected]/@value)'));
    }

    public function test_untrusted_content_is_escaped_including_the_page_title_and_full_text(): void
    {
        $payload = '<script>alert("CyberZone")</script>';
        $this->category->update(['category_name' => $payload]);
        $blog = Blog::create($this->validData([
            'title' => $payload, 'description' => $payload, 'text' => $payload."\nДругий рядок",
        ]));

        foreach (['blog.index', 'blog.show', 'blog.edit'] as $route) {
            $parameters = $route === 'blog.index' ? [] : [$blog];
            $this->get(route($route, $parameters))->assertOk()->assertSee($payload)->assertDontSee($payload, false);
        }

        $this->get(route('blog.admin'))->assertOk()->assertSee($payload)->assertDontSee($payload, false);

        $this->get(route('blog.show', $blog))->assertSee(e($payload)."<br />\nДругий рядок", false);
    }

    public function test_unknown_blog_returns_404_for_resource_operations(): void
    {
        $this->get(route('blog.show', 999999))->assertNotFound();
        $this->get(route('blog.edit', 999999))->assertNotFound();
        $this->put(route('blog.update', 999999), $this->validData())->assertNotFound();
        $this->delete(route('blog.destroy', 999999))->assertNotFound();
    }

    private function createBlogs(int $count): array
    {
        $blogs = [];
        for ($number = 1; $number <= $count; $number++) {
            $blog = new Blog($this->validData([
                'title' => sprintf('Новина %02d', $number),
                'text' => sprintf('Повний текст новини %02d.', $number),
            ]));
            $blog->dt_created = Carbon::parse('2026-11-01 12:00:00')->addMinutes($number);
            $blog->save();
            $blogs[] = $blog;
        }

        return $blogs;
    }

    private function nodeTexts(string $html, string $query): array
    {
        $texts = [];
        foreach ($this->document($html)->query($query) as $node) {
            $texts[] = trim($node->textContent);
        }

        return $texts;
    }

    private function validData(array $changes = []): array
    {
        return array_replace([
            'title' => 'Новий турнір CyberZone',
            'description' => 'Запрошуємо команди на клубний турнір.',
            'text' => 'У CyberZone відбудеться турнір для команд. Реєстрація доступна в адміністратора клубу.',
            'category_id' => $this->category->getKey(),
        ], $changes);
    }

    private function document(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new DOMXPath($document);
    }
}

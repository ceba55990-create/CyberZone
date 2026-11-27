<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\BlogTranslation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BlogTranslationTest extends TestCase
{
    private Blog $blog;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests\TestCase guards SQLite :memory: before these migrations run.
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_11_27_000000_create_category_table.php',
            '2026_11_27_000001_create_blog_table.php',
            '2026_11_27_000002_create_blog_translations_table.php',
        ] as $migrationFile) {
            (require database_path('migrations/'.$migrationFile))->up();
        }

        $this->blog = new Blog([
            'title' => 'Новини CyberZone',
            'description' => 'Базовий український опис.',
            'text' => 'Базовий український текст для використання за відсутності перекладу.',
        ]);
        $this->blog->blog_id = 42;
        $this->blog->save();
    }

    public function test_migration_creates_columns_unique_index_and_cascading_foreign_key(): void
    {
        $this->assertSame([
            'id', 'blog_id', 'locale', 'title', 'description', 'text', 'created_at', 'updated_at',
        ], Schema::getColumnListing('blog_translations'));

        $columns = collect(Schema::getColumns('blog_translations'))->keyBy('name');
        foreach (['blog_id', 'locale', 'title'] as $column) {
            $this->assertFalse($columns[$column]['nullable']);
        }
        $this->assertTrue($columns['description']['nullable']);
        $this->assertTrue($columns['text']['nullable']);

        $this->assertTrue(collect(Schema::getIndexes('blog_translations'))->contains(
            fn (array $index) => $index['unique'] && $index['columns'] === ['blog_id', 'locale'],
        ));

        $foreignKeys = Schema::getForeignKeys('blog_translations');
        $this->assertCount(1, $foreignKeys);
        $this->assertSame(['blog_id'], $foreignKeys[0]['columns']);
        $this->assertSame('blog', $foreignKeys[0]['foreign_table']);
        $this->assertSame(['blog_id'], $foreignKeys[0]['foreign_columns']);
        $this->assertSame('cascade', $foreignKeys[0]['on_delete']);
    }

    public function test_relations_store_multiple_languages_for_one_blog_using_its_custom_key(): void
    {
        $baseContent = $this->blog->only(['title', 'description', 'text']);
        $english = $this->blog->translations()->create([
            'locale' => 'en', 'title' => 'CyberZone news',
            'description' => 'English description.', 'text' => 'English news content.',
        ]);
        $polish = BlogTranslation::create([
            'blog_id' => $this->blog->getKey(), 'locale' => 'pl', 'title' => 'Aktualności CyberZone',
        ]);
        $this->blog->translations()->create(['locale' => 'uk', 'title' => 'Український переклад']);

        $blog = Blog::with('translations')->findOrFail(42);
        $this->assertCount(3, $blog->translations);
        $this->assertEqualsCanonicalizing(['en', 'pl', 'uk'], $blog->translations->pluck('locale')->all());
        $this->assertSame(42, $english->blog_id);
        $this->assertTrue($english->blog->is($blog));
        $this->assertTrue($polish->blog->is($blog));
        $this->assertTrue($blog->translations->contains(fn (BlogTranslation $translation) => $translation->is($english)));
        $this->assertSame($baseContent, $blog->only(['title', 'description', 'text']));
        $this->assertDatabaseCount('blog', 1);
        $this->assertDatabaseCount('blog_translations', 3);
        $this->assertDatabaseHas('blog_translations', [
            'id' => $english->getKey(), 'blog_id' => 42, 'locale' => 'en',
            'description' => 'English description.', 'text' => 'English news content.',
        ]);
    }

    public function test_duplicate_locale_for_the_same_blog_is_rejected_by_the_database(): void
    {
        $this->blog->translations()->create(['locale' => 'en', 'title' => 'First translation']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('UNIQUE constraint failed: blog_translations.blog_id, blog_translations.locale');

        $this->blog->translations()->create(['locale' => 'en', 'title' => 'Duplicate translation']);
    }

    public function test_updating_a_locale_cannot_create_a_duplicate_pair(): void
    {
        $this->blog->translations()->create(['locale' => 'en', 'title' => 'English translation']);
        $polish = $this->blog->translations()->create(['locale' => 'pl', 'title' => 'Polish translation']);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('UNIQUE constraint failed: blog_translations.blog_id, blog_translations.locale');

        $polish->update(['locale' => 'en']);
    }

    public function test_the_same_locale_can_be_used_for_different_blogs(): void
    {
        $other = Blog::create(['title' => 'Інша новина']);
        foreach ([$this->blog, $other] as $blog) {
            $blog->translations()->create(['locale' => 'en', 'title' => 'English translation']);
        }

        $this->assertSame(1, $this->blog->translations()->count());
        $this->assertSame(1, $other->translations()->count());
        $this->assertDatabaseCount('blog_translations', 2);
    }

    public function test_deleting_a_blog_cascades_only_to_its_translations(): void
    {
        $this->blog->translations()->create(['locale' => 'en', 'title' => 'English translation']);
        $this->blog->translations()->create(['locale' => 'pl', 'title' => 'Polish translation']);
        $other = Blog::create(['title' => 'Інша новина']);
        $retained = $other->translations()->create(['locale' => 'en', 'title' => 'Retained translation']);

        // Delete directly to verify the database constraint, independently of model events.
        DB::table('blog')->where('blog_id', $this->blog->getKey())->delete();

        $this->assertDatabaseMissing('blog_translations', ['blog_id' => 42]);
        $this->assertDatabaseHas('blog_translations', ['id' => $retained->getKey(), 'blog_id' => $other->getKey()]);
        $this->assertDatabaseCount('blog_translations', 1);
        $this->assertDatabaseHas('blog', ['blog_id' => $other->getKey()]);
    }

    public function test_translation_requires_an_existing_blog(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('FOREIGN KEY constraint failed');

        BlogTranslation::create(['blog_id' => 999999, 'locale' => 'en', 'title' => 'Orphan translation']);
    }

    public function test_translation_uses_standard_timestamps_and_allows_nullable_content(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 10:00:00'));
        $translation = $this->blog->translations()->create(['locale' => 'en', 'title' => 'English title']);
        $this->assertSame('2026-11-27 10:00:00', $translation->created_at->toDateTimeString());
        $this->assertSame('2026-11-27 10:00:00', $translation->updated_at->toDateTimeString());
        $translation->refresh();
        $this->assertNull($translation->description);
        $this->assertNull($translation->text);

        $this->travelTo(Carbon::parse('2026-11-27 11:00:00'));
        $translation->update(['title' => 'Updated English title']);
        $translation->refresh();
        $this->assertSame('2026-11-27 10:00:00', $translation->created_at->toDateTimeString());
        $this->assertSame('2026-11-27 11:00:00', $translation->updated_at->toDateTimeString());
        $this->travelBack();
    }

    public function test_blog_without_translations_keeps_its_base_ukrainian_content(): void
    {
        $this->assertTrue($this->blog->translations->isEmpty());
        $this->assertSame('Новини CyberZone', $this->blog->title);
        $this->assertSame('Базовий український опис.', $this->blog->description);
        $this->assertSame('Базовий український текст для використання за відсутності перекладу.', $this->blog->text);
    }

    public function test_migration_can_be_rolled_back_without_removing_the_base_blog(): void
    {
        $migration = require database_path('migrations/2026_11_27_000002_create_blog_translations_table.php');
        $migration->down();

        $this->assertFalse(Schema::hasTable('blog_translations'));
        $this->assertDatabaseHas('blog', ['blog_id' => 42, 'title' => 'Новини CyberZone']);

        $migration->up();
        $this->assertTrue(Schema::hasTable('blog_translations'));
    }
}

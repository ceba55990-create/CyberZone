<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\BlogTranslation;
use Database\Seeders\BlogSeeder;
use Database\Seeders\BlogTranslationSeeder;
use RuntimeException;
use Tests\TestCase;

class BlogTranslationSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests\TestCase verifies the SQLite :memory: connection before any writes.
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_11_27_000000_create_category_table.php',
            '2026_11_27_000001_create_blog_table.php',
            '2026_11_27_000002_create_blog_translations_table.php',
        ] as $migrationFile) {
            (require database_path('migrations/'.$migrationFile))->up();
        }

        // Real databases need not assign IDs 1 through 8 to the original news.
        $placeholder = new Blog(['title' => 'Тимчасовий тестовий запис']);
        $placeholder->blog_id = 100;
        $placeholder->save();
        $placeholder->delete();

        $this->seed(BlogSeeder::class);
    }

    public function test_seeder_creates_sixteen_linked_translations_without_changing_base_news(): void
    {
        $before = $this->blogsSnapshot();
        $this->seed(BlogTranslationSeeder::class);

        $this->assertDatabaseCount('blog', 8);
        $this->assertDatabaseCount('blog_translations', 16);
        $this->assertSame($before, $this->blogsSnapshot());

        foreach (Blog::with('translations.blog')->get() as $blog) {
            $this->assertGreaterThan(100, $blog->getKey());
            $this->assertCount(2, $blog->translations);
            $this->assertEqualsCanonicalizing(['uk', 'en'], $blog->translations->pluck('locale')->all());
            $ukrainian = $blog->translations->firstWhere('locale', 'uk');
            $this->assertSame($blog->only(['title', 'description', 'text']), $ukrainian->only(['title', 'description', 'text']));

            foreach ($blog->translations as $translation) {
                $this->assertSame($blog->getKey(), $translation->blog_id);
                $this->assertTrue($translation->blog->is($blog));
                $this->assertTrue(mb_check_encoding($translation->only(['title', 'description', 'text']), 'UTF-8'));
                $this->assertLessThanOrEqual(200, mb_strlen($translation->title));
                $this->assertLessThanOrEqual(500, mb_strlen($translation->description));
                $this->assertNotEmpty($translation->text);
                $this->assertLessThanOrEqual(65535, strlen($translation->text));
            }
        }
    }

    public function test_english_content_matches_each_original_and_keeps_proper_names(): void
    {
        $this->seed(BlogTranslationSeeder::class);

        foreach ($this->expectedEnglishContent() as $originalTitle => $expected) {
            $blog = Blog::where('title', $originalTitle)->firstOrFail();
            $english = $blog->translations()->where('locale', 'en')->firstOrFail();

            $this->assertSame($expected['title'], $english->title);
            $this->assertSame($expected['description'], $english->description);
            $this->assertStringStartsWith($expected['opening'], $english->text);
            $this->assertStringContainsString($expected['detail'], $english->text);
            $this->assertGreaterThan(250, mb_strlen($english->text));
            foreach (['title', 'description', 'text'] as $field) {
                $this->assertSame(0, preg_match('/\p{Cyrillic}/u', $english->{$field}));
            }
            foreach (['CyberZone', 'Counter-Strike 2', 'Dota 2'] as $name) {
                if (str_contains($originalTitle, $name)) {
                    $this->assertStringContainsString($name, $english->title);
                }
            }
        }
    }

    public function test_repeated_seeding_updates_existing_pairs_without_duplicates_or_base_changes(): void
    {
        $before = $this->blogsSnapshot();
        $this->seed(BlogTranslationSeeder::class);
        $translations = BlogTranslation::orderBy('id')->get();
        $ids = $translations->modelKeys();
        $createdAt = $translations->mapWithKeys(fn (BlogTranslation $translation) => [
            $translation->getKey() => $translation->getRawOriginal('created_at'),
        ])->all();
        $english = $translations->firstWhere('locale', 'en');
        $ukrainian = $translations->firstWhere('locale', 'uk');
        $expectedEnglish = $english->only(['title', 'description', 'text']);
        $expectedUkrainian = $ukrainian->only(['title', 'description', 'text']);
        foreach ([$english, $ukrainian] as $translation) {
            $translation->update(['title' => 'Застарілий переклад', 'description' => 'Старий опис', 'text' => 'Старий текст']);
        }

        $this->travel(1)->hours();
        $this->seed(BlogTranslationSeeder::class);
        $this->seed(BlogTranslationSeeder::class);
        $this->travelBack();

        $after = BlogTranslation::orderBy('id')->get();
        $this->assertDatabaseCount('blog_translations', 16);
        $this->assertSame($ids, $after->modelKeys());
        $this->assertSame($createdAt, $after->mapWithKeys(fn (BlogTranslation $translation) => [
            $translation->getKey() => $translation->getRawOriginal('created_at'),
        ])->all());
        $this->assertSame($expectedEnglish, $english->fresh()->only(['title', 'description', 'text']));
        $this->assertSame($expectedUkrainian, $ukrainian->fresh()->only(['title', 'description', 'text']));
        $this->assertSame($before, $this->blogsSnapshot());
        $this->assertSame(16, $after->map(fn (BlogTranslation $translation) => $translation->blog_id.':'.$translation->locale)->unique()->count());
    }

    public function test_missing_original_reports_its_title_and_rolls_back_all_translation_changes(): void
    {
        // Removing the final source ensures earlier writes must be rolled back.
        $missing = Blog::where('title', 'Відкриті тренування для початківців у кіберспорті')->firstOrFail();
        $missingTitle = $missing->title;
        $missing->delete();
        $first = Blog::where('title', 'CyberZone відкриває зону для командних тренувань')->firstOrFail();
        $existing = $first->translations()->create([
            'locale' => 'en', 'title' => 'Existing translation',
            'description' => 'Keep this description.', 'text' => 'Keep this text.',
        ]);
        $beforeBlogs = $this->blogsSnapshot();
        $beforeTranslation = $existing->refresh()->getAttributes();

        try {
            $this->seed(BlogTranslationSeeder::class);
            $this->fail('Seeding must fail when an original news record is missing.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Не знайдено новину', $exception->getMessage());
            $this->assertStringContainsString($missingTitle, $exception->getMessage());
        }

        $this->assertDatabaseCount('blog', 7);
        $this->assertDatabaseCount('blog_translations', 1);
        $this->assertDatabaseMissing('blog', ['title' => $missingTitle]);
        $this->assertSame($beforeBlogs, $this->blogsSnapshot());
        $this->assertSame($beforeTranslation, $existing->fresh()->getAttributes());
    }

    public function test_seeder_preserves_other_languages_and_does_not_translate_unrelated_news(): void
    {
        $blog = Blog::firstOrFail();
        $polish = $blog->translations()->create([
            'locale' => 'pl', 'title' => 'Aktualności CyberZone', 'text' => 'Polski tekst.',
        ]);
        $polish->refresh();
        $beforePolish = $polish->getAttributes();
        $unrelated = Blog::create(['title' => 'Додаткова новина поза BlogSeeder']);
        $beforeBlogs = $this->blogsSnapshot();

        $this->seed(BlogTranslationSeeder::class);

        $this->assertDatabaseCount('blog_translations', 17);
        $this->assertSame($beforePolish, $polish->fresh()->getAttributes());
        $this->assertSame(0, $unrelated->translations()->count());
        $this->assertSame($beforeBlogs, $this->blogsSnapshot());
    }

    private function blogsSnapshot(): array
    {
        return Blog::orderBy('blog_id')->get()->toArray();
    }

    private function expectedEnglishContent(): array
    {
        return [
            'CyberZone відкриває зону для командних тренувань' => [
                'title' => 'CyberZone opens a team training area',
                'description' => 'The club has set up a dedicated area for teams preparing for competitions and practising together.',
                'opening' => 'A new team training area with adjacent gaming PCs is now available at CyberZone.',
                'detail' => 'agreeing on the number of participants and the length of the session in advance',
            ],
            'У CyberZone запроваджено попереднє бронювання ПК' => [
                'title' => 'Advance PC bookings are now available at CyberZone',
                'description' => 'Visitors can arrange gaming stations in advance with the club administrator.',
                'opening' => 'To plan a visit to CyberZone, you can now book a PC in advance through the administrator.',
                'detail' => 'notify the club in advance so that other visitors can use the available computers',
            ],
            'Оновлення ігрових ПК: більше потужності для сучасних ігор' => [
                'title' => 'Gaming PC upgrades: more power for modern games',
                'description' => 'CyberZone is upgrading some gaming stations and testing them before returning them to the gaming floor.',
                'opening' => 'The club is upgrading its gaming PCs in stages,',
                'detail' => 'Ask the administrator which upgraded stations are available for your session.',
            ],
            'Нові монітори для змагальних матчів' => [
                'title' => 'New monitors for competitive matches',
                'description' => 'High refresh rate monitors have been installed at selected stations for fast-paced games.',
                'opening' => 'CyberZone is upgrading the monitors at stations used for competitive matches.',
                'detail' => 'contact the administrator before starting a match',
            ],
            'Профілактика обладнання та оновлення ігрової бібліотеки' => [
                'title' => 'Hardware maintenance and game library updates',
                'description' => 'The club team is servicing PCs and preparing game clients for upcoming sessions.',
                'opening' => 'During scheduled maintenance, CyberZone technicians remove dust from PC cases',
                'detail' => 'let you know when the serviced PC is ready to use again',
            ],
            'Турнір із Counter-Strike 2 у CyberZone' => [
                'title' => 'Counter-Strike 2 tournament at CyberZone',
                'description' => 'Teams are invited to a club Counter-Strike 2 tournament with advance registration.',
                'opening' => 'CyberZone is preparing a club Counter-Strike 2 tournament for the local gaming community.',
                'detail' => 'check its settings, peripherals and voice chat',
            ],
            'Командний вечір Dota 2 для спільноти клубу' => [
                'title' => 'Dota 2 team night for the club community',
                'description' => 'Dota 2 players are invited to friendly matches, opportunities to meet new teammates and discussions about team play.',
                'opening' => 'CyberZone will host a Dota 2 team night featuring friendly matches between visitors.',
                'detail' => 'discuss key moments and arrange future training sessions together',
            ],
            'Відкриті тренування для початківців у кіберспорті' => [
                'title' => 'Open training sessions for esports beginners',
                'description' => 'CyberZone invites newcomers to learn the basics of team communication and match preparation.',
                'opening' => 'The club is holding open training sessions for visitors who want to try team competitions.',
                'detail' => 'specify the game you are most interested in',
            ],
        ];
    }
}

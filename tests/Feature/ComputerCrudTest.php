<?php

namespace Tests\Feature;

use App\Models\Computer;
use Database\Seeders\ComputerSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ComputerCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ComputerSeeder::class);
    }

    public function test_list_has_five_rows_per_page_and_twelve_computers_in_total(): void
    {
        $response = $this->get(route('computers.index'))->assertOk()->assertViewIs('computers.index')
            ->assertViewHas('computers', fn (LengthAwarePaginator $computers) => $computers->total() === 12 && $computers->perPage() === 5);

        $this->assertSame($this->names(1, 5), $this->renderedNames($response->getContent()));
        $second = $this->get(route('computers.index', ['page' => 2]))->assertOk();
        $this->assertSame($this->names(6, 10), $this->renderedNames($second->getContent()));
        $third = $this->get(route('computers.index', ['page' => 3]))->assertOk();
        $this->assertSame($this->names(11, 12), $this->renderedNames($third->getContent()));
    }

    #[DataProvider('statusFilters')]
    public function test_each_status_filter_has_the_expected_records(string $status, int $total, array $names): void
    {
        $response = $this->get(route('computers.index', ['status' => $status]))->assertOk()
            ->assertViewHas('computers', fn (LengthAwarePaginator $computers) => $computers->total() === $total);

        $this->assertSame($names, $this->renderedNames($response->getContent()));
        $this->assertSame($status, $this->document($response->getContent())->evaluate('string(//select[@id="status-filter"]/option[@selected]/@value)'));
    }

    public static function statusFilters(): array
    {
        return [
            'available' => ['available', 7, ['CyberZone PC-01', 'CyberZone PC-02', 'CyberZone PC-03', 'CyberZone PC-04', 'CyberZone PC-05']],
            'occupied' => ['occupied', 3, ['CyberZone PC-08', 'CyberZone PC-09', 'CyberZone PC-10']],
            'maintenance' => ['maintenance', 2, ['CyberZone PC-11', 'CyberZone PC-12']],
        ];
    }

    public function test_second_filtered_page_keeps_the_status_and_contains_only_the_remaining_available_pcs(): void
    {
        $first = $this->get(route('computers.index', ['status' => 'available']))->assertOk();
        $xpath = $this->document($first->getContent());
        $nextUrl = $xpath->evaluate('string(//nav[@class="computer-pagination"]//a[@rel="next"]/@href)');
        parse_str(parse_url($nextUrl, PHP_URL_QUERY), $query);
        $this->assertSame(['status' => 'available', 'page' => '2'], $query);

        $second = $this->get($nextUrl)->assertOk();
        $this->assertSame(['CyberZone PC-06', 'CyberZone PC-07'], $this->renderedNames($second->getContent()));
        $xpath = $this->document($second->getContent());
        $this->assertSame('available', $xpath->evaluate('string(//select[@id="status-filter"]/option[@selected]/@value)'));
        $this->assertSame(2, $xpath->query('//tbody//span[contains(@class,"computer-status-available")]')->length);
        $this->assertSame(0, $xpath->query('//tbody//span[contains(@class,"computer-status-occupied") or contains(@class,"computer-status-maintenance")]')->length);
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_safely_show_all_computers(mixed $status): void
    {
        $this->get(route('computers.index', ['status' => $status]))->assertOk()
            ->assertViewHas('status', '')
            ->assertViewHas('computers', fn (LengthAwarePaginator $computers) => $computers->total() === 12);
    }

    public static function invalidFilters(): array
    {
        return [
            'unknown' => ['broken'],
            'array' => [['available']],
            'empty' => [''],
            'SQL-like text' => ["available' OR 1=1 --"],
        ];
    }

    public function test_seeder_can_run_twice_without_overwriting_existing_or_manual_records(): void
    {
        Computer::where('name', 'CyberZone PC-01')->firstOrFail()->update([
            'cpu' => 'User configured CPU', 'hourly_rate' => '77.77', 'status' => 'maintenance',
        ]);
        Computer::create($this->validData(['name' => 'Manual PC']));
        $before = Computer::orderBy('id')->get()->toArray();

        $this->seed(ComputerSeeder::class);
        $this->seed(ComputerSeeder::class);

        $this->assertSame($before, Computer::orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('computers', 13);
    }

    public function test_create_form_has_csrf_and_store_saves_only_validated_data(): void
    {
        $this->get(route('computers.create'))->assertOk()->assertViewIs('computers.create')
            ->assertSee('name="_token"', false);

        $data = $this->validData();
        $this->post(route('computers.store'), $data + ['id' => 999999])
            ->assertRedirectToRoute('computers.index')->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'ПК додано.');

        $computer = Computer::where('name', $data['name'])->firstOrFail();
        $this->assertNotSame(999999, $computer->id);
        $this->assertSame(16, $computer->ram_gb);
        $this->assertSame(1000, $computer->storage_gb);
        $this->assertSame('49.50', $computer->hourly_rate);
        $this->assertDatabaseCount('computers', 13);
        $this->get(route('computers.index'))->assertOk()->assertSee('ПК додано.');
    }

    public function test_show_includes_storage_and_a_confirmed_delete_form(): void
    {
        $computer = Computer::where('name', 'CyberZone PC-01')->firstOrFail();

        $this->get(route('computers.show', $computer))->assertOk()->assertViewIs('computers.show')
            ->assertSee('CyberZone PC-01')->assertSee('AMD Ryzen 5 5600')
            ->assertSee('NVIDIA GeForce RTX 3060')->assertSee('512 ГБ')->assertSee('45,00 грн')
            ->assertSee('Вільний')->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('name="_token"', false)->assertSee('onsubmit="return confirm(', false);
    }

    public function test_edit_prefills_fields_and_update_accepts_its_own_name(): void
    {
        $computer = Computer::where('name', 'CyberZone PC-01')->firstOrFail();
        $this->get(route('computers.edit', $computer))->assertOk()->assertViewIs('computers.edit')
            ->assertSee('value="CyberZone PC-01"', false)->assertSee('value="45.00"', false)
            ->assertSee('name="_method" value="PUT"', false);

        $this->put(route('computers.update', $computer), $this->validData([
            'name' => $computer->name, 'status' => 'occupied', 'hourly_rate' => '70.25',
        ]))->assertRedirectToRoute('computers.index')->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Дані ПК оновлено.');

        $computer->refresh();
        $this->assertSame('70.25', $computer->hourly_rate);
        $this->assertSame('occupied', $computer->status);
        $this->assertDatabaseCount('computers', 12);
    }

    public function test_update_can_rename_a_computer(): void
    {
        $computer = Computer::where('name', 'CyberZone PC-01')->firstOrFail();
        $this->put(route('computers.update', $computer), $this->validData(['name' => 'Renamed PC']))
            ->assertRedirectToRoute('computers.index')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('computers', ['id' => $computer->id, 'name' => 'Renamed PC']);
    }

    public function test_duplicate_names_are_rejected_for_creation_and_update(): void
    {
        $data = $this->validData(['name' => 'CyberZone PC-02']);
        $this->from(route('computers.create'))->post(route('computers.store'), $data)
            ->assertRedirectToRoute('computers.create')->assertSessionHasErrors('name');
        $computer = Computer::where('name', 'CyberZone PC-01')->firstOrFail();
        $this->from(route('computers.edit', $computer))->put(route('computers.update', $computer), $data)
            ->assertRedirectToRoute('computers.edit', $computer)->assertSessionHasErrors('name');

        $this->assertDatabaseHas('computers', ['id' => $computer->id, 'name' => 'CyberZone PC-01']);
        $this->assertDatabaseCount('computers', 12);
    }

    public function test_destroy_removes_only_the_selected_test_computer(): void
    {
        $computer = Computer::create($this->validData());
        $this->delete(route('computers.destroy', $computer))->assertRedirectToRoute('computers.index')
            ->assertSessionHas('success', 'ПК видалено.');

        $this->assertDatabaseMissing('computers', ['id' => $computer->id]);
        $this->assertDatabaseCount('computers', 12);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_input_is_rejected_without_creating_a_computer(array $changes, array $errors, bool $empty = false): void
    {
        $data = $empty ? [] : $this->validData($changes);
        $this->from(route('computers.create'))->post(route('computers.store'), $data)
            ->assertRedirectToRoute('computers.create')->assertSessionHasErrors($errors);

        $this->get(route('computers.create'))->assertOk();
        $this->assertDatabaseCount('computers', 12);
    }

    public static function invalidInputs(): array
    {
        return [
            'missing fields' => [[], ['name', 'cpu', 'gpu', 'ram_gb', 'storage_gb', 'hourly_rate', 'status'], true],
            'long name' => [['name' => str_repeat('Ї', 101)], ['name']],
            'long cpu' => [['cpu' => str_repeat('C', 151)], ['cpu']],
            'long gpu' => [['gpu' => str_repeat('G', 151)], ['gpu']],
            'zero RAM' => [['ram_gb' => 0], ['ram_gb']],
            'large RAM' => [['ram_gb' => 1025], ['ram_gb']],
            'fractional RAM' => [['ram_gb' => '1.5'], ['ram_gb']],
            'zero storage' => [['storage_gb' => 0], ['storage_gb']],
            'large storage' => [['storage_gb' => 100001], ['storage_gb']],
            'fractional storage' => [['storage_gb' => '2.5'], ['storage_gb']],
            'negative price' => [['hourly_rate' => '-0.01'], ['hourly_rate']],
            'large price' => [['hourly_rate' => '1000000.00'], ['hourly_rate']],
            'price with three decimal places' => [['hourly_rate' => '12.345'], ['hourly_rate']],
            'non-numeric price' => [['hourly_rate' => 'free'], ['hourly_rate']],
            'invalid status' => [['status' => 'broken'], ['status']],
        ];
    }

    public function test_validation_accepts_numeric_and_string_boundaries(): void
    {
        $this->post(route('computers.store'), $this->validData([
            'name' => str_repeat('Ї', 100), 'cpu' => str_repeat('C', 150), 'gpu' => str_repeat('G', 150),
            'ram_gb' => 1024, 'storage_gb' => 100000, 'hourly_rate' => '999999.99',
        ]))->assertRedirectToRoute('computers.index')->assertSessionHasNoErrors();

        $this->post(route('computers.store'), $this->validData([
            'name' => 'Minimum PC', 'ram_gb' => 1, 'storage_gb' => 1, 'hourly_rate' => '0',
        ]))->assertRedirectToRoute('computers.index')->assertSessionHasNoErrors();
    }

    #[DataProvider('arrayInputs')]
    public function test_old_values_and_array_input_are_safely_rendered(array $fields): void
    {
        $data = $this->validData(['name' => 'QA "PC" <test>']);

        foreach ($fields as $field) {
            $data[$field] = [$data[$field]];
        }

        $this->from(route('computers.create'))->post(route('computers.store'), $data)
            ->assertRedirectToRoute('computers.create')->assertSessionHasErrors($fields);
        $response = $this->get(route('computers.create'))->assertOk();
        $xpath = $this->document($response->getContent());

        foreach ($data as $field => $value) {
            $actual = $field === 'status'
                ? $xpath->evaluate('string(//select[@id="status"]/option[@selected]/@value)')
                : $xpath->evaluate('string(//input[@name="'.$field.'"]/@value)');
            $this->assertSame(is_array($value) ? '' : (string) $value, $actual);

            if (in_array($field, $fields, true)) {
                $this->assertSame(1, $xpath->query('//*[@id="'.$field.'-error"]')->length);
            }
        }

        $this->assertDatabaseCount('computers', 12);
    }

    public static function arrayInputs(): array
    {
        $fields = ['name', 'cpu', 'gpu', 'ram_gb', 'storage_gb', 'hourly_rate', 'status'];
        $cases = [];

        foreach ($fields as $field) {
            $cases[$field.' array'] = [[$field]];
        }

        $cases['all arrays'] = [$fields];

        return $cases;
    }

    public function test_missing_computer_returns_404(): void
    {
        $this->get(route('computers.show', 999999))->assertNotFound();
        $this->get(route('computers.edit', 999999))->assertNotFound();
        $this->put(route('computers.update', 999999), $this->validData())->assertNotFound();
        $this->delete(route('computers.destroy', 999999))->assertNotFound();
    }

    public function test_html_is_escaped_and_homepage_computer_anchor_is_preserved(): void
    {
        $name = '<script>alert(1)</script>';
        $computer = Computer::create($this->validData(['name' => $name]));

        foreach ([route('computers.index'), route('computers.show', $computer)] as $url) {
            $this->get($url)->assertOk()->assertSee($name)->assertDontSee($name, false);
        }

        $this->get(route('home'))->assertOk()->assertSee('id="computers"', false)
            ->assertSee('href="'.route('home').'#computers"', false)
            ->assertSee('href="'.route('computers.index').'">Комп\'ютери</a>', false);
    }

    private function validData(array $changes = []): array
    {
        return array_replace([
            'name' => 'QA PC', 'cpu' => 'AMD Ryzen 5 5600', 'gpu' => 'NVIDIA GeForce RTX 3060',
            'ram_gb' => 16, 'storage_gb' => 1000, 'hourly_rate' => '49.50', 'status' => 'available',
        ], $changes);
    }

    private function names(int $first, int $last): array
    {
        return array_map(fn (int $number) => sprintf('CyberZone PC-%02d', $number), range($first, $last));
    }

    private function renderedNames(string $html): array
    {
        $names = [];

        foreach ($this->document($html)->query('//tbody/tr/td[1]') as $cell) {
            $names[] = trim($cell->textContent);
        }

        return $names;
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

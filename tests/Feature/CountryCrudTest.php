<?php

namespace Tests\Feature;

use App\Models\Country;
use Database\Seeders\CountrySeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CountryCrudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CountrySeeder::class);
    }

    public function test_index_sorts_and_paginates_ten_countries_with_continuous_row_numbers(): void
    {
        $first = $this->get(route('country.index'))->assertOk()
            ->assertViewIs('country.index')
            ->assertViewHas('countries', fn (LengthAwarePaginator $countries) => $countries->total() === 10 && $countries->perPage() === 5)
            ->assertSeeInOrder(['Australia', 'Brazil', 'Canada', 'China', 'France'])
            ->assertDontSee('Germany')
            ->assertSee('href="'.route('country.index', ['page' => 2]).'"', false)
            ->assertSee('aria-current="page"', false);

        $second = $this->get(route('country.index', ['page' => 2]))->assertOk()
            ->assertSeeInOrder(['Germany', 'India', 'Ukraine', 'United Kingdom', 'United States'])
            ->assertDontSee('Australia');

        foreach ([[$first, ['1', '2', '3', '4', '5']], [$second, ['6', '7', '8', '9', '10']]] as [$response, $numbers]) {
            $cells = $this->document($response->getContent())->query('//tbody/tr/td[1]');
            $actual = [];

            foreach ($cells as $cell) {
                $actual[] = trim($cell->textContent);
            }

            $this->assertSame($numbers, $actual);
        }
    }

    public function test_create_form_and_navigation_use_the_shared_layout(): void
    {
        $this->get(route('country.create'))->assertOk()
            ->assertViewIs('country.create')
            ->assertSee('method="POST" action="'.route('country.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('CYBER<span>ZONE</span>', false)
            ->assertSee('href="'.route('country.index').'">Країни</a>', false);
    }

    public function test_show_binds_the_string_code_and_contains_a_confirmed_delete_form(): void
    {
        $this->get(route('country.show', 'UA'))->assertOk()
            ->assertViewIs('country.show')
            ->assertViewHas('country', fn (Country $country) => $country->code === 'UA')
            ->assertSee('Ukraine')
            ->assertSee('41 167 335')
            ->assertSee('action="'.route('country.destroy', 'UA').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('onsubmit="return confirm(', false);
    }

    public function test_store_creates_only_validated_fields_and_flashes_success(): void
    {
        $this->post(route('country.store'), [
            'code' => 'ZZ',
            'name' => 'Testland',
            'population' => 12345,
            'created_at' => '2000-01-01',
        ])->assertRedirectToRoute('country.index')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Країну додано.');

        $this->assertDatabaseHas('country', ['code' => 'ZZ', 'name' => 'Testland', 'population' => 12345]);
        $this->assertDatabaseCount('country', 11);
        $this->get(route('country.index'))->assertOk()->assertSee('Країну додано.');
    }

    public function test_validation_accepts_the_name_and_population_boundaries(): void
    {
        $this->post(route('country.store'), [
            'code' => 'ZZ', 'name' => str_repeat('Ї', 52), 'population' => 2147483647,
        ])->assertRedirectToRoute('country.index')->assertSessionHasNoErrors();

        $this->post(route('country.store'), [
            'code' => 'ZY', 'name' => 'Zero', 'population' => 0,
        ])->assertRedirectToRoute('country.index')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('country', ['code' => 'ZY', 'population' => 0]);
    }

    #[DataProvider('invalidCountries')]
    public function test_store_rejects_invalid_input_without_adding_records(array $data, array $errors): void
    {
        $this->from(route('country.create'))->post(route('country.store'), $data)
            ->assertRedirectToRoute('country.create')->assertSessionHasErrors($errors);

        $this->get(route('country.create'))->assertOk();
        $this->assertDatabaseCount('country', 10);
    }

    public static function invalidCountries(): array
    {
        $valid = ['code' => 'ZZ', 'name' => 'Testland', 'population' => 10];

        return [
            'missing fields' => [[], ['code', 'name', 'population']],
            'whitespace fields' => [['code' => '  ', 'name' => '  ', 'population' => ' '], ['code', 'name', 'population']],
            'short code' => [array_replace($valid, ['code' => 'Z']), ['code']],
            'long code' => [array_replace($valid, ['code' => 'ZZZ']), ['code']],
            'long name' => [array_replace($valid, ['name' => str_repeat('Ї', 53)]), ['name']],
            'negative population' => [array_replace($valid, ['population' => -1]), ['population']],
            'fractional population' => [array_replace($valid, ['population' => '1.5']), ['population']],
            'text population' => [array_replace($valid, ['population' => 'many']), ['population']],
            'population exceeds database integer' => [array_replace($valid, ['population' => 2147483648]), ['population']],
        ];
    }

    #[DataProvider('invalidCountryCodes')]
    public function test_store_rejects_invalid_country_code_formats(string $code): void
    {
        $this->from(route('country.create'))->post(route('country.store'), [
            'code' => $code, 'name' => 'Testland', 'population' => 10,
        ])->assertRedirectToRoute('country.create')->assertSessionHasErrors([
            'code' => 'Код країни має містити рівно дві великі латинські літери A-Z.',
        ]);

        $this->assertDatabaseMissing('country', ['code' => $code]);
        $this->assertDatabaseCount('country', 10);
    }

    #[DataProvider('invalidCountryCodes')]
    public function test_update_rejects_invalid_country_codes_and_preserves_the_existing_country(string $code): void
    {
        $original = Country::findOrFail('UA')->getAttributes();

        $this->from(route('country.edit', 'UA'))->put(route('country.update', 'UA'), [
            'code' => $code, 'name' => 'Changed name', 'population' => 1,
        ])->assertRedirectToRoute('country.edit', 'UA')->assertSessionHasErrors([
            'code' => 'Код країни має містити рівно дві великі латинські літери A-Z.',
        ]);

        $this->assertDatabaseHas('country', $original);
        $this->assertDatabaseMissing('country', ['code' => $code]);
        $this->assertDatabaseCount('country', 10);
        $this->get(route('country.show', 'UA'))->assertOk()->assertSee('Ukraine');
        $this->get(route('country.edit', 'UA'))->assertOk();
    }

    public static function invalidCountryCodes(): array
    {
        return [
            'slash' => ['A/'],
            'slashes only' => ['//'],
            'question mark' => ['A?'],
            'digit' => ['1A'],
            'lowercase' => ['aa'],
        ];
    }

    #[DataProvider('validCountryCodes')]
    public function test_valid_country_codes_support_creation_update_and_bound_routes(string $code): void
    {
        Country::where('code', $code)->delete();
        $data = ['code' => $code, 'name' => 'Testland', 'population' => 10];

        $this->post(route('country.store'), $data)
            ->assertRedirectToRoute('country.index')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('country', $data);
        $this->get(route('country.show', $code))->assertOk()->assertSee('Testland');
        $this->get(route('country.edit', $code))->assertOk()
            ->assertSee('value="'.$code.'"', false);

        $data['name'] = 'Testland updated';
        $this->put(route('country.update', $code), $data)
            ->assertRedirectToRoute('country.index')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('country', $data);

        $this->delete(route('country.destroy', $code))->assertRedirectToRoute('country.index');
        $this->assertDatabaseMissing('country', ['code' => $code]);
        $this->get(route('country.show', $code))->assertNotFound();
        $this->get(route('country.edit', $code))->assertNotFound();
    }

    public static function validCountryCodes(): array
    {
        return ['UA' => ['UA'], 'ZZ' => ['ZZ']];
    }

    #[DataProvider('arrayFields')]
    public function test_array_input_is_safe_and_other_old_values_are_preserved(array $arrayFields): void
    {
        $data = ['code' => 'ZZ', 'name' => 'Test "Name" <club>', 'population' => '7'];

        foreach ($arrayFields as $field) {
            $data[$field] = [$data[$field]];
        }

        $this->from(route('country.create'))->post(route('country.store'), $data)
            ->assertRedirectToRoute('country.create')->assertSessionHasErrors($arrayFields);

        $response = $this->get(route('country.create'))->assertOk();
        $xpath = $this->document($response->getContent());

        foreach ($data as $field => $value) {
            $this->assertSame(is_array($value) ? '' : $value, $xpath->evaluate('string(//input[@name="'.$field.'"]/@value)'));

            if (in_array($field, $arrayFields, true)) {
                $this->assertSame(1, $xpath->query('//input[@name="'.$field.'" and @aria-describedby="'.$field.'-error"]/following-sibling::p[@id="'.$field.'-error"]')->length);
            }
        }

        $this->assertDatabaseCount('country', 10);
    }

    public static function arrayFields(): array
    {
        return [
            'code array' => [['code']],
            'name array' => [['name']],
            'population array' => [['population']],
            'all arrays' => [['code', 'name', 'population']],
        ];
    }

    public function test_required_errors_appear_next_to_each_field(): void
    {
        $this->from(route('country.create'))->post(route('country.store'), [])
            ->assertRedirectToRoute('country.create');

        $this->get(route('country.create'))->assertOk()
            ->assertSee('Вкажіть код країни.')
            ->assertSee('Вкажіть назву країни.')
            ->assertSee('Вкажіть населення країни.')
            ->assertSee('id="code-error"', false)
            ->assertSee('id="name-error"', false)
            ->assertSee('id="population-error"', false);
    }

    public function test_store_rejects_duplicate_code_and_preserves_the_original_record(): void
    {
        $this->from(route('country.create'))->post(route('country.store'), [
            'code' => 'UA', 'name' => 'Duplicate', 'population' => 1,
        ])->assertRedirectToRoute('country.create')->assertSessionHasErrors('code');

        $this->assertDatabaseHas('country', ['code' => 'UA', 'name' => 'Ukraine', 'population' => 41167335]);
        $this->assertDatabaseCount('country', 10);
    }

    public function test_edit_form_prefills_fields_and_uses_put(): void
    {
        $response = $this->get(route('country.edit', 'UA'))->assertOk()
            ->assertViewIs('country.edit')
            ->assertSee('action="'.route('country.update', 'UA').'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('name="_token"', false);
        $xpath = $this->document($response->getContent());

        foreach (['code' => 'UA', 'name' => 'Ukraine', 'population' => '41167335'] as $field => $value) {
            $this->assertSame($value, $xpath->evaluate('string(//input[@name="'.$field.'"]/@value)'));
        }
    }

    public function test_update_accepts_its_own_code(): void
    {
        $this->put(route('country.update', 'UA'), [
            'code' => 'UA', 'name' => 'Ukraine updated', 'population' => 41167335,
        ])->assertRedirectToRoute('country.index')->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Країну оновлено.');

        $this->assertDatabaseHas('country', ['code' => 'UA', 'name' => 'Ukraine updated']);
        $this->assertDatabaseCount('country', 10);
    }

    public function test_update_can_change_the_string_primary_key(): void
    {
        Country::create(['code' => 'ZZ', 'name' => 'Testland', 'population' => 1]);

        $this->put(route('country.update', 'ZZ'), [
            'code' => 'ZY', 'name' => 'Testland updated', 'population' => 2,
        ])->assertRedirectToRoute('country.index')->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('country', ['code' => 'ZZ']);
        $this->assertDatabaseHas('country', ['code' => 'ZY', 'name' => 'Testland updated', 'population' => 2]);
        $this->assertDatabaseCount('country', 11);
        $this->get(route('country.show', 'ZZ'))->assertNotFound();
        $this->get(route('country.show', 'ZY'))->assertOk()->assertSee('Testland updated');
    }

    public function test_update_rejects_another_countrys_code_and_keeps_old_form_input(): void
    {
        Country::create(['code' => 'ZZ', 'name' => 'Testland', 'population' => 1]);

        $this->from(route('country.edit', 'ZZ'))->put(route('country.update', 'ZZ'), [
            'code' => 'UA', 'name' => 'Changed name', 'population' => 2,
        ])->assertRedirectToRoute('country.edit', 'ZZ')->assertSessionHasErrors('code');

        $this->get(route('country.edit', 'ZZ'))->assertOk()
            ->assertSee('value="UA"', false)
            ->assertSee('value="Changed name"', false)
            ->assertSee('Країна з таким кодом уже існує.');
        $this->assertDatabaseHas('country', ['code' => 'ZZ', 'name' => 'Testland', 'population' => 1]);
        $this->assertDatabaseHas('country', ['code' => 'UA', 'name' => 'Ukraine']);
    }

    public function test_destroy_deletes_only_the_selected_test_country(): void
    {
        Country::create(['code' => 'ZZ', 'name' => 'Testland', 'population' => 1]);

        $this->delete(route('country.destroy', 'ZZ'))->assertRedirectToRoute('country.index')
            ->assertSessionHas('success', 'Країну видалено.');

        $this->assertDatabaseMissing('country', ['code' => 'ZZ']);
        $this->assertDatabaseCount('country', 10);
        $this->assertDatabaseHas('country', ['code' => 'UA', 'name' => 'Ukraine']);
    }

    public function test_missing_country_returns_404_for_bound_routes(): void
    {
        $this->get(route('country.show', 'ZZ'))->assertNotFound();
        $this->get(route('country.edit', 'ZZ'))->assertNotFound();
        $this->put(route('country.update', 'ZZ'), ['code' => 'ZZ', 'name' => 'Missing', 'population' => 0])->assertNotFound();
        $this->delete(route('country.destroy', 'ZZ'))->assertNotFound();
    }

    public function test_country_names_are_escaped_in_the_list_and_detail_page(): void
    {
        $name = '<script>alert(1)</script>';
        Country::create(['code' => 'ZZ', 'name' => $name, 'population' => 0]);

        foreach ([route('country.index'), route('country.show', 'ZZ')] as $url) {
            $this->get($url)->assertOk()->assertSee($name)->assertDontSee($name, false);
        }
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

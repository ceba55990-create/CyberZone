<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    public function test_original_contact_form_still_displays_the_club_name_and_all_fields(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertViewIs('contact')
            ->assertViewHas('clubName', 'CyberZone')
            ->assertSee('action="'.route('contact.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('type="date"', false)
            ->assertSee('name="visit_date"', false)
            ->assertSee("Бажана дата відвідування (необов'язково)", false)
            ->assertSee('name="message"', false);
    }

    public function test_contact_form_still_requires_all_three_fields(): void
    {
        $this->from(route('contact.index'))->post(route('contact.store'), [])
            ->assertRedirectToRoute('contact.index')
            ->assertSessionHasErrors(['name', 'email', 'message'])
            ->assertSessionDoesntHaveErrors(['visit_date']);

        $this->get(route('contact.index'))
            ->assertOk()
            ->assertSee('Вкажіть ваше ім\'я.')
            ->assertSee('Вкажіть електронну пошту.')
            ->assertSee('Введіть текст повідомлення.');
    }

    public function test_contact_form_keeps_old_values_when_email_is_invalid(): void
    {
        $this->from(route('contact.index'))->post(route('contact.store'), [
            'name' => 'Іван',
            'email' => 'test',
            'visit_date' => '2026-11-20',
            'message' => 'Які тарифи у CyberZone?',
        ])->assertRedirectToRoute('contact.index')
            ->assertSessionHasErrors(['email'])
            ->assertSessionDoesntHaveErrors(['name', 'visit_date', 'message'])
            ->assertSessionHasInput('name', 'Іван')
            ->assertSessionHasInput('visit_date', '2026-11-20')
            ->assertSessionHasInput('message', 'Які тарифи у CyberZone?');

        $this->get(route('contact.index'))
            ->assertOk()
            ->assertSee('value="Іван"', false)
            ->assertSee('value="test"', false)
            ->assertSee('value="2026-11-20"', false)
            ->assertSee('Які тарифи у CyberZone?')
            ->assertSee('Введіть коректну електронну адресу.');
    }

    #[DataProvider('arrayContactFields')]
    public function test_contact_form_handles_array_input_and_preserves_valid_strings(array $arrayFields): void
    {
        $data = [
            'name' => 'Іван "Гравець"',
            'email' => 'ivan@example.com',
            'visit_date' => '2026-11-20',
            'message' => 'Які тарифи & умови <бронювання>?',
        ];

        foreach ($arrayFields as $field) {
            $data[$field] = [$data[$field]];
        }

        $response = $this->from(route('contact.index'))->post(route('contact.store'), $data)
            ->assertRedirectToRoute('contact.index')
            ->assertSessionHasErrors($arrayFields);

        foreach (array_diff(array_keys($data), $arrayFields) as $field) {
            $response->assertSessionDoesntHaveErrors([$field])
                ->assertSessionHasInput($field, $data[$field]);
        }

        $form = $this->get(route('contact.index'))->assertOk();
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$form->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);

        foreach ($data as $field => $value) {
            $elements = $xpath->query('//form//input[@name="'.$field.'"] | //form//textarea[@name="'.$field.'"]');
            $this->assertCount(1, $elements);
            $element = $elements->item(0);
            $actual = $field === 'message' ? $element->textContent : $element->getAttribute('value');
            $this->assertSame(is_string($value) ? $value : '', $actual, "Unexpected value for {$field}");
        }
    }

    public static function arrayContactFields(): array
    {
        return [
            'array name' => [['name']],
            'array email' => [['email']],
            'array visit date' => [['visit_date']],
            'array message' => [['message']],
            'all fields are arrays' => [['name', 'email', 'visit_date', 'message']],
        ];
    }

    #[DataProvider('validVisitDates')]
    public function test_contact_form_accepts_an_optional_visit_date(?string $visitDate): void
    {
        $this->post(route('contact.store'), [
            'name' => 'Іван',
            'email' => 'ivan@example.com',
            'visit_date' => $visitDate,
            'message' => 'Хочу відвідати CyberZone.',
        ])->assertOk()
            ->assertSessionHasNoErrors()
            ->assertViewIs('contact-success')
            ->assertViewHas('name', 'Іван')
            ->assertSee('Форму успішно заповнено!');
    }

    public static function validVisitDates(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'whitespace' => ['   '],
            'valid date' => ['2026-11-20'],
            'leap day' => ['2024-02-29'],
            'first day of the year' => ['2026-01-01'],
            'last day of the year' => ['2026-12-31'],
        ];
    }

    #[DataProvider('invalidVisitDates')]
    public function test_contact_form_rejects_invalid_visit_dates_and_keeps_old_input(mixed $visitDate): void
    {
        $this->from(route('contact.index'))->post(route('contact.store'), [
            'name' => 'Іван',
            'email' => 'ivan@example.com',
            'visit_date' => $visitDate,
            'message' => 'Хочу відвідати CyberZone.',
        ])->assertRedirectToRoute('contact.index')
            ->assertSessionHasErrors([
                'visit_date' => 'Вкажіть коректну дату відвідування у форматі РРРР-ММ-ДД.',
            ])
            ->assertSessionDoesntHaveErrors(['name', 'email', 'message'])
            ->assertSessionHasInput('name', 'Іван')
            ->assertSessionHasInput('email', 'ivan@example.com')
            ->assertSessionHasInput('visit_date', $visitDate)
            ->assertSessionHasInput('message', 'Хочу відвідати CyberZone.');

        $this->get(route('contact.index'))
            ->assertOk()
            ->assertSee('value="Іван"', false)
            ->assertSee('value="ivan@example.com"', false)
            ->assertSee('value="'.e(is_string($visitDate) ? $visitDate : '').'"', false)
            ->assertSee('Хочу відвідати CyberZone.')
            ->assertSee('Вкажіть коректну дату відвідування у форматі РРРР-ММ-ДД.');
    }

    public static function invalidVisitDates(): array
    {
        return [
            'text' => ['not-a-date'],
            'different format' => ['20.11.2026'],
            'missing leading zeros' => ['2026-2-3'],
            'non-leap day' => ['2026-02-29'],
            'impossible day' => ['2026-04-31'],
            'invalid month' => ['2026-13-01'],
            'zero month' => ['2026-00-10'],
            'zero day' => ['2026-01-00'],
            'date with time' => ['2026-11-20 18:00:00'],
            'HTML' => ['"><script>alert(1)</script>'],
            'integer' => [20261120],
            'boolean' => [true],
            'array' => [['2026-11-20']],
            'empty array' => [[]],
        ];
    }

    public function test_contact_form_still_uses_its_own_length_limits(): void
    {
        $this->from(route('contact.index'))->post(route('contact.store'), [
            'name' => str_repeat('І', 101),
            'email' => 'ivan@example.com',
            'message' => str_repeat('а', 2001),
        ])->assertRedirectToRoute('contact.index')
            ->assertSessionHasErrors(['name', 'message'])
            ->assertSessionDoesntHaveErrors(['email']);
    }

    public function test_valid_contact_form_still_displays_the_original_confirmation(): void
    {
        $this->post('/contact', [
            'name' => 'Іван',
            'email' => 'ivan@example.com',
            'message' => 'Хочу дізнатися про тарифи.',
        ])->assertOk()
            ->assertSessionHasNoErrors()
            ->assertViewIs('contact-success')
            ->assertViewHas('name', 'Іван')
            ->assertSee('Форму успішно заповнено!')
            ->assertSee('Дякуємо, Іван!')
            ->assertSee('href="'.route('contact.index').'"', false);
    }
}

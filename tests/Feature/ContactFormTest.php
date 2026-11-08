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
            ->assertSee('name="message"', false);
    }

    public function test_contact_form_still_requires_all_three_fields(): void
    {
        $this->from(route('contact.index'))->post(route('contact.store'), [])
            ->assertRedirectToRoute('contact.index')
            ->assertSessionHasErrors(['name', 'email', 'message']);

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
            'message' => 'Які тарифи у CyberZone?',
        ])->assertRedirectToRoute('contact.index')
            ->assertSessionHasErrors(['email'])
            ->assertSessionDoesntHaveErrors(['name', 'message'])
            ->assertSessionHasInput('name', 'Іван')
            ->assertSessionHasInput('message', 'Які тарифи у CyberZone?');

        $this->get(route('contact.index'))
            ->assertOk()
            ->assertSee('value="Іван"', false)
            ->assertSee('value="test"', false)
            ->assertSee('Які тарифи у CyberZone?')
            ->assertSee('Введіть коректну електронну адресу.');
    }

    #[DataProvider('arrayContactFields')]
    public function test_contact_form_handles_array_input_and_preserves_valid_strings(array $arrayFields): void
    {
        $data = [
            'name' => 'Іван "Гравець"',
            'email' => 'ivan@example.com',
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
            'array message' => [['message']],
            'all fields are arrays' => [['name', 'email', 'message']],
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

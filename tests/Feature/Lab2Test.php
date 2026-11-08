<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Lab2Test extends TestCase
{
    public function test_say_displays_the_default_message_in_the_shared_layout(): void
    {
        $this->get('/say')
            ->assertOk()
            ->assertViewIs('site.say')
            ->assertViewHas('message', 'Привіт')
            ->assertSee('<h1>Привіт</h1>', false)
            ->assertSee('CYBER<span>ZONE</span>', false)
            ->assertSee('<footer>', false);
    }

    public function test_say_displays_a_message_from_the_url(): void
    {
        $message = 'Вітаю з CyberZone!';

        $this->get('/say/'.rawurlencode($message))
            ->assertOk()
            ->assertViewHas('message', $message)
            ->assertSee('<h1>'.$message.'</h1>', false);
    }

    public function test_say_escapes_html_from_the_url(): void
    {
        $message = '<script>alert(1)';

        $this->get('/say/'.rawurlencode($message))
            ->assertOk()
            ->assertSee($message)
            ->assertDontSee($message, false);
    }

    public function test_entry_form_uses_post_and_contains_a_csrf_token(): void
    {
        $this->get('/entry')
            ->assertOk()
            ->assertViewIs('site.entry')
            ->assertSee('method="POST" action="'.route('entry.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false);
    }

    #[DataProvider('invalidEntries')]
    public function test_entry_rejects_invalid_data(array $data, array $errors): void
    {
        $this->from(route('entry.form'))
            ->post(route('entry.store'), $data)
            ->assertRedirectToRoute('entry.form')
            ->assertSessionHasErrors($errors);

        $this->get(route('entry.form'))->assertOk();
    }

    public static function invalidEntries(): array
    {
        return [
            'missing fields' => [[], ['name', 'email']],
            'empty fields' => [['name' => '', 'email' => ''], ['name', 'email']],
            'whitespace fields' => [['name' => '   ', 'email' => '   '], ['name', 'email']],
            'non-string name' => [['name' => ['Іван'], 'email' => 'ivan@example.com'], ['name']],
            'name exceeds 255 characters' => [['name' => str_repeat('І', 256), 'email' => 'ivan@example.com'], ['name']],
            'invalid email' => [['name' => 'Іван', 'email' => 'test'], ['email']],
            'non-string email' => [['name' => 'Іван', 'email' => ['ivan@example.com']], ['email']],
        ];
    }

    public function test_entry_shows_required_errors_next_to_the_fields(): void
    {
        $this->from(route('entry.form'))->post(route('entry.store'), [])
            ->assertRedirectToRoute('entry.form');

        $this->get(route('entry.form'))
            ->assertOk()
            ->assertSee('id="name-error"', false)
            ->assertSee('Поле «Ім\'я» не може бути порожнім.')
            ->assertSee('id="email-error"', false)
            ->assertSee('Поле «Email» не може бути порожнім.');
    }

    public function test_entry_keeps_old_input_and_displays_an_email_error(): void
    {
        $this->from(route('entry.form'))->post(route('entry.store'), [
            'name' => 'Іван "Гравець"',
            'email' => 'test',
        ])->assertRedirectToRoute('entry.form')
            ->assertSessionHasErrors(['email'])
            ->assertSessionDoesntHaveErrors(['name'])
            ->assertSessionHasInput('name', 'Іван "Гравець"')
            ->assertSessionHasInput('email', 'test');

        $this->get(route('entry.form'))
            ->assertOk()
            ->assertSee('value="'.e('Іван "Гравець"').'"', false)
            ->assertSee('value="test"', false)
            ->assertSee('Email введено некоректно.');
    }

    public function test_entry_confirms_only_validated_data_and_escapes_the_name(): void
    {
        $name = '<script>alert(1)</script>';

        $this->post(route('entry.store'), [
            'name' => $name,
            'email' => 'ivan@example.com',
            'message' => 'Unexpected field',
        ])->assertOk()
            ->assertSessionHasNoErrors()
            ->assertViewIs('site.entry-confirm')
            ->assertViewHas('name', $name)
            ->assertViewHas('email', 'ivan@example.com')
            ->assertViewMissing('message')
            ->assertSee($name)
            ->assertDontSee($name, false)
            ->assertSee('ivan@example.com')
            ->assertSee('href="'.route('entry.form').'"', false);
    }

    public function test_entry_accepts_a_name_of_exactly_255_characters(): void
    {
        $name = str_repeat('І', 255);

        $this->post(route('entry.store'), [
            'name' => $name,
            'email' => 'ivan@example.com',
        ])->assertOk()
            ->assertSessionHasNoErrors()
            ->assertViewIs('site.entry-confirm')
            ->assertViewHas('name', $name);
    }

    public function test_about_displays_the_information_page(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertViewIs('site.about')
            ->assertSee('Про застосунок')
            ->assertSee('CyberZone')
            ->assertSee('href="'.route('contact.index').'"', false);
    }

    public function test_all_navigation_links_resolve_and_homepage_anchors_exist(): void
    {
        $response = $this->get(route('home'))->assertOk();
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $links = (new DOMXPath($document))->query('//nav//a | //footer//a | //main//a');

        $this->assertGreaterThan(0, $links->length);

        foreach ($links as $link) {
            $url = parse_url($link->getAttribute('href'));
            $page = $this->get($url['path'] ?? '/')->assertOk();

            if (isset($url['fragment'])) {
                $page->assertSee('id="'.$url['fragment'].'"', false);
            }
        }
    }
}

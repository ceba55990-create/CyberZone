<?php

namespace Tests\Feature;

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

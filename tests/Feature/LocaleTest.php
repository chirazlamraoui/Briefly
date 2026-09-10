<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_switch_to_french(): void
    {
        $this->get(route('locale.switch', 'fr'))
            ->assertRedirect()
            ->assertSessionHas('locale', 'fr');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Se connecter', false);
    }

    public function test_user_can_switch_to_english(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->get(route('locale.switch', 'en'))
            ->assertRedirect()
            ->assertSessionHas('locale', 'en');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in', false);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $this->get('/locale/de')->assertNotFound();
    }
}

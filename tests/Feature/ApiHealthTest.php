<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiHealthTest extends TestCase
{
    public function test_api_health_confirms_the_database_connection(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'database' => 'connected',
            ]);
    }

    public function test_homepage_displays_login_form_for_guests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sign in to ClubPilot')
            ->assertSee('name="_token"', false);
    }

    public function test_login_page_has_a_csrf_protected_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to ClubPilot')
            ->assertSee('name="_token"', false);
    }
}

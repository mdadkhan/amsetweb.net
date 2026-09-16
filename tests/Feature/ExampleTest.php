<?php

namespace Tests\Feature;

use App\Models\Initiative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        foreach (['AMSET AI Advocacy', 'Science Fair', 'AMSET Webinar', 'Contribute to AMSET'] as $index => $name) {
            Initiative::query()->create([
                'name' => $name,
                'slug' => str($name)->slug(),
                'sort_order' => $index,
                'is_published' => true,
            ]);
        }

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Where scientific rigor meets public purpose.')
            ->assertSee('AMSET AI Advocacy')
            ->assertSee('Science Fair')
            ->assertSee('AMSET Webinar')
            ->assertSee('Contribute to AMSET')
            ->assertSee('images/amset-logo.png')
            ->assertSee('New dispatches are in preparation.');
    }

    public function test_homepage_contains_accessible_responsive_navigation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('aria-controls="primary-navigation"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('name="viewport"', false)
            ->assertSee('Skip to content');
    }
}

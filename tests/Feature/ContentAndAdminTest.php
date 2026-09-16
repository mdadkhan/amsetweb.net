<?php

namespace Tests\Feature;

use App\Models\Conference;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAndAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_public_content_sections_are_available(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/about-us')->assertOk()->assertSee('History at a glance');
        $this->get('/conferences')->assertOk()->assertSee('2025 Annual Conference');
        $this->get('/news')->assertOk()->assertSee('Welcome to the new AMSET website');
        $this->get('/eminent-scientists')->assertOk()->assertSee('Salman Hameed');
        $this->get('/gallery')->assertOk()->assertSee('Annual Conference Highlights');
    }

    public function test_admin_panel_requires_an_authorized_user(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $regularUser = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regularUser)->get('/admin')->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('AMSET Administration');
    }

    public function test_public_conference_archive_uses_the_admin_managed_conferences_table(): void
    {
        Conference::query()->create([
            'title' => '2026 Annual Conference',
            'slug' => '2026-annual-conference',
            'is_published' => true,
        ]);
        Post::query()->create([
            'title' => 'Legacy conference post',
            'slug' => 'legacy-conference-post',
            'category' => 'conference',
            'published_at' => now(),
        ]);

        $this->get('/conferences')
            ->assertOk()
            ->assertSee('2026 Annual Conference')
            ->assertDontSee('Legacy conference post');
    }
}

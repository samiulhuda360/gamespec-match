<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_pages_render_from_a_fresh_seed(): void
    {
        foreach (['/', '/games', '/games/free', '/games/latest', '/games/popular', '/games/deals', '/search/advanced',
            '/search/advanced?q=survival', '/hardware/add-specs', '/ask', '/login', '/register', '/forgot-password',
            '/sitemap.xml', '/game/cyberpunk-2077', '/collections/latest-releases'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_game_page_shows_the_parsed_requirements(): void
    {
        $this->get('/game/cyberpunk-2077')
            ->assertOk()
            ->assertSee('Cyberpunk 2077')
            ->assertSee('GeForce GTX 1060 6GB');
    }

    public function test_old_urls_redirect_and_keep_the_query(): void
    {
        $this->get('/hardware/create')->assertRedirect('/hardware/add-specs');
        $this->get('/search?q=cyber')->assertRedirect(route('search.advanced', ['q' => 'cyber']));
        $this->get('/dashboard')->assertRedirect('/account');
    }

    public function test_the_theme_has_no_purple(): void
    {
        $html = $this->get('/')->getContent();

        foreach (['#8a5cff', '138,92,255', '#667eea', '#764ba2'] as $purple) {
            $this->assertStringNotContainsString($purple, $html);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 6: search (FR-14).
 *
 * One search box, three tabs - people (name, subject or bio), hashtags and
 * posts. Blocked accounts never appear, the LIKE wildcards inside the term are
 * escaped, and a term shorter than two characters is not searched at all.
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_a_logged_in_user(): void
    {
        $this->get(route('search.index'))->assertRedirect(route('login'));
    }

    public function test_the_search_box_is_in_the_navbar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee(route('search.index'), false);
    }

    /* ---------------------------------------------------------------
     | People tab
     * --------------------------------------------------------------- */

    public function test_users_are_found_by_name(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Lovelace', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('Ada Lovelace');
    }

    public function test_users_are_found_by_subject(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create([
            'name' => 'Grace Hopper',
            'subject' => 'Computer Science',
        ]);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Computer', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('Grace Hopper');
    }

    public function test_users_are_found_by_bio(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create([
            'name' => 'Grace Hopper',
            'bio' => 'I teach compiler design and COBOL.',
        ]);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'COBOL', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('Grace Hopper');
    }

    public function test_blocked_users_are_not_in_the_results(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->blocked()->create(['name' => 'Hidden Hubert']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Hubert', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('Hidden Hubert');
    }

    public function test_a_term_shorter_than_two_characters_is_not_searched(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'a', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('Type at least two characters')
            ->assertDontSee('Ada Lovelace');
    }

    public function test_admins_are_not_in_the_results(): void
    {
        $me = User::factory()->create();
        User::factory()->admin()->create(['name' => 'Admin Alice']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'Alice', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('Admin Alice');
    }

    public function test_an_empty_term_does_not_list_anybody(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => '', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('Ada Lovelace');
    }

    /* ---------------------------------------------------------------
     | Hashtag tab
     * --------------------------------------------------------------- */

    public function test_hashtags_are_found_by_name(): void
    {
        $me = User::factory()->create();
        // The model lowercases the name, exactly like the feed does.
        Hashtag::create(['name' => 'algebra']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'alge', 'tab' => 'hashtags']))
            ->assertOk()
            ->assertSee('algebra');
    }

    public function test_a_hashtag_that_does_not_exist_is_not_found(): void
    {
        $me = User::factory()->create();
        Hashtag::create(['name' => 'algebra']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'geometry', 'tab' => 'hashtags']))
            ->assertOk()
            ->assertSee('No hashtag matches');
    }

    public function test_a_hashtag_is_found_with_or_without_the_hash_symbol(): void
    {
        $me = User::factory()->create();
        Hashtag::create(['name' => 'algebra']);

        // "#algebra" and "algebra" must behave identically.
        foreach (['algebra', '#algebra'] as $term) {
            $this->actingAs($me)
                ->get(route('search.index', ['q' => $term, 'tab' => 'hashtags']))
                ->assertOk()
                ->assertSee('algebra');
        }
    }

    /* ---------------------------------------------------------------
     | Posts tab
     * --------------------------------------------------------------- */

    public function test_posts_are_found_by_their_body_text(): void
    {
        $me = User::factory()->student()->create();
        $post = Post::factory()->create(['body' => 'Revision notes about photosynthesis']);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'photosynthesis', 'tab' => 'posts']))
            ->assertOk()
            // The card itself is rendered, not just the term echoed back.
            ->assertSee('data-post-card="'.$post->id.'"', false);
    }

    public function test_hidden_posts_and_posts_by_blocked_users_stay_out_of_the_results(): void
    {
        $me = User::factory()->student()->create();

        $hidden = Post::factory()->hidden()->create(['body' => 'A hidden remark about photosynthesis']);

        $blockedAuthor = User::factory()->teacher()->blocked()->create();
        $blocked = Post::factory()->create([
            'user_id' => $blockedAuthor->id,
            'body' => 'A blocked remark about photosynthesis',
        ]);

        $this->actingAs($me)
            ->get(route('search.index', ['q' => 'photosynthesis', 'tab' => 'posts']))
            ->assertOk()
            ->assertDontSee('data-post-card="'.$hidden->id.'"', false)
            ->assertDontSee('data-post-card="'.$blocked->id.'"', false);
    }

    /* ---------------------------------------------------------------
     | Live suggestions
     * --------------------------------------------------------------- */

    public function test_the_suggestion_endpoint_returns_people_and_hashtags(): void
    {
        $me = User::factory()->student()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);
        Hashtag::create(['name' => 'adaptive']);

        $this->actingAs($me)
            ->getJson(route('search.suggest', ['q' => 'ada']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Ada Lovelace'])
            ->assertJsonFragment(['label' => '#adaptive']);
    }

    public function test_the_suggestion_endpoint_shows_at_most_five_entries(): void
    {
        $me = User::factory()->student()->create();
        User::factory()->count(8)->teacher()->create(['name' => 'Tutor Number']);

        $this->actingAs($me)
            ->getJson(route('search.suggest', ['q' => 'Tutor']))
            ->assertOk()
            ->assertJsonCount(5, 'suggestions');
    }

    public function test_the_suggestion_endpoint_stays_empty_for_a_short_term(): void
    {
        $me = User::factory()->student()->create();
        User::factory()->teacher()->create(['name' => 'Ada Lovelace']);

        $this->actingAs($me)
            ->getJson(route('search.suggest', ['q' => 'a']))
            ->assertOk()
            ->assertJsonCount(0, 'suggestions');
    }

    /* ---------------------------------------------------------------
     | Safety
     * --------------------------------------------------------------- */

    public function test_like_wildcards_in_the_term_are_escaped(): void
    {
        $me = User::factory()->create();
        User::factory()->teacher()->create(['name' => 'Wildcard Wanda']);

        // "%" must be searched as a literal character, not as "everything".
        $this->actingAs($me)
            ->get(route('search.index', ['q' => '%', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('Wildcard Wanda');
    }

    public function test_the_term_is_escaped_in_the_output(): void
    {
        $me = User::factory()->create();

        // A script tag in the query string is printed escaped, never executed.
        $this->actingAs($me)
            ->get(route('search.index', ['q' => '<script>alert(1)</script>', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}

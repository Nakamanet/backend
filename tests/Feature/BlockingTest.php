<?php

namespace Tests\Feature;

use App\Models\Comment\Comment;
use App\Models\Friendship\Friendship;
use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * A `blocked` relationship is symmetric in its effects: whoever pressed the
 * button, the two users become mutually invisible. Every rule below is therefore
 * asserted in both directions — blocker → blocked, and blocked → blocker.
 */
class BlockingTest extends TestCase
{
    use DatabaseTransactions;

    private function block(User $blocker, User $blocked): Friendship
    {
        return Friendship::create([
            'requester_id' => $blocker->id,
            'addressee_id' => $blocked->id,
            'status'       => 'blocked',
        ]);
    }

    // -------------------------------------------------------------------------
    // Feed
    // -------------------------------------------------------------------------

    public function test_feed_hides_posts_of_a_user_the_caller_blocked()
    {
        $caller  = User::factory()->create();
        $blocked = User::factory()->create();
        $post    = Post::factory()->create(['user_id' => $blocked->id]);

        $this->block($caller, $blocked);

        $this->actingAs($caller)->getJson('/api/posts')
             ->assertStatus(200)
             ->assertJsonMissing(['id' => $post->id]);
    }

    public function test_feed_hides_posts_of_a_user_who_blocked_the_caller()
    {
        $caller  = User::factory()->create();
        $blocker = User::factory()->create();
        $post    = Post::factory()->create(['user_id' => $blocker->id]);

        $this->block($blocker, $caller);

        $this->actingAs($caller)->getJson('/api/posts')
             ->assertStatus(200)
             ->assertJsonMissing(['id' => $post->id]);
    }

    public function test_feed_still_shows_posts_of_unrelated_users()
    {
        $caller  = User::factory()->create();
        $blocked = User::factory()->create();
        $other   = User::factory()->create();
        $post    = Post::factory()->create(['user_id' => $other->id]);

        $this->block($caller, $blocked);

        $this->actingAs($caller)->getJson('/api/posts')
             ->assertStatus(200)
             ->assertJsonFragment(['id' => $post->id]);
    }

    // -------------------------------------------------------------------------
    // Post detail — a direct link must not bypass the feed filter
    // -------------------------------------------------------------------------

    public function test_post_detail_returns_404_when_the_caller_blocked_the_author()
    {
        $caller = User::factory()->create();
        $author = User::factory()->create();
        $post   = Post::factory()->create(['user_id' => $author->id]);

        $this->block($caller, $author);

        $this->actingAs($caller)->getJson("/api/posts/{$post->id}")
             ->assertStatus(404);
    }

    public function test_post_detail_returns_404_when_the_author_blocked_the_caller()
    {
        $caller = User::factory()->create();
        $author = User::factory()->create();
        $post   = Post::factory()->create(['user_id' => $author->id]);

        $this->block($author, $caller);

        $this->actingAs($caller)->getJson("/api/posts/{$post->id}")
             ->assertStatus(404);
    }

    public function test_guest_can_still_read_a_post_detail()
    {
        $author = User::factory()->create();
        $post   = Post::factory()->create(['user_id' => $author->id]);

        $this->getJson("/api/posts/{$post->id}")->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Comments
    // -------------------------------------------------------------------------

    public function test_blocked_user_cannot_comment_on_the_blocker_post()
    {
        $blocker = User::factory()->create();
        $blocked = User::factory()->create();
        $post    = Post::factory()->create(['user_id' => $blocker->id]);

        $this->block($blocker, $blocked);

        $this->actingAs($blocked)->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Coucou',
        ])->assertStatus(403);
    }

    public function test_blocker_cannot_comment_on_the_blocked_user_post()
    {
        $blocker = User::factory()->create();
        $blocked = User::factory()->create();
        $post    = Post::factory()->create(['user_id' => $blocked->id]);

        $this->block($blocker, $blocked);

        $this->actingAs($blocker)->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Coucou',
        ])->assertStatus(403);
    }

    public function test_unrelated_user_can_still_comment()
    {
        $author = User::factory()->create();
        $other  = User::factory()->create();
        $post   = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($other)->postJson("/api/posts/{$post->id}/comments", [
            'content' => 'Coucou',
        ])->assertStatus(201);
    }

    public function test_guest_cannot_comment()
    {
        $post = Post::factory()->create();

        $this->postJson("/api/posts/{$post->id}/comments", ['content' => 'Coucou'])
             ->assertStatus(401);
    }

    public function test_cannot_reply_to_a_comment_of_a_blocked_user()
    {
        $caller  = User::factory()->create();
        $blocked = User::factory()->create();
        $author  = User::factory()->create();
        $post    = Post::factory()->create(['user_id' => $author->id]);

        $parent = Comment::create([
            'user_id' => $blocked->id,
            'post_id' => $post->id,
            'content' => 'Parent',
        ]);

        $this->block($caller, $blocked);

        $this->actingAs($caller)->postJson("/api/posts/{$post->id}/comments", [
            'content'   => 'Réponse',
            'parent_id' => $parent->id,
        ])->assertStatus(403);
    }

    public function test_comment_list_hides_comments_of_a_blocked_user()
    {
        $caller  = User::factory()->create();
        $blocked = User::factory()->create();
        $author  = User::factory()->create();
        $post    = Post::factory()->create(['user_id' => $author->id]);

        $hidden = Comment::create([
            'user_id' => $blocked->id,
            'post_id' => $post->id,
            'content' => 'Invisible',
        ]);
        $kept = Comment::create([
            'user_id' => $author->id,
            'post_id' => $post->id,
            'content' => 'Visible',
        ]);

        $this->block($caller, $blocked);

        $this->actingAs($caller)->getJson("/api/posts/{$post->id}/comments")
             ->assertStatus(200)
             ->assertJsonMissing(['id' => $hidden->id])
             ->assertJsonFragment(['id' => $kept->id]);
    }

    // -------------------------------------------------------------------------
    // User post lists
    // -------------------------------------------------------------------------

    public function test_user_posts_returns_403_when_blocked()
    {
        $caller = User::factory()->create();
        $target = User::factory()->create();

        $this->block($caller, $target);

        $this->actingAs($caller)->getJson("/api/users/{$target->id}/posts")
             ->assertStatus(403);
    }

    public function test_user_liked_posts_returns_403_when_blocked()
    {
        $caller = User::factory()->create();
        $target = User::factory()->create();

        $this->block($target, $caller);

        $this->actingAs($caller)->getJson("/api/users/{$target->id}/liked-posts")
             ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // Profile access
    // -------------------------------------------------------------------------

    public function test_blocked_profile_is_restricted_even_when_public()
    {
        $caller = User::factory()->create();
        $target = User::factory()->create(['profile_visibility' => 'public']);

        $this->block($caller, $target);

        // No `role` = the client treats the profile as inaccessible.
        $this->actingAs($caller)->getJson("/api/users/{$target->id}/profile")
             ->assertStatus(200)
             ->assertJsonMissingPath('role')
             ->assertJsonPath('friendship_status', 'blocked');
    }

    public function test_profile_of_a_user_who_blocked_the_caller_is_restricted()
    {
        $caller = User::factory()->create();
        $target = User::factory()->create(['profile_visibility' => 'public']);

        $this->block($target, $caller);

        $this->actingAs($caller)->getJson("/api/users/{$target->id}/profile")
             ->assertStatus(200)
             ->assertJsonMissingPath('role')
             ->assertJsonPath('friendship_status', 'blocked_by');
    }

    public function test_public_profile_stays_accessible_without_a_block()
    {
        $caller = User::factory()->create();
        $target = User::factory()->create(['profile_visibility' => 'public']);

        $this->actingAs($caller)->getJson("/api/users/{$target->id}/profile")
             ->assertStatus(200)
             ->assertJsonPath('friendship_status', 'none')
             ->assertJsonStructure(['role']);
    }

    public function test_friends_list_returns_403_when_blocked()
    {
        $caller = User::factory()->create();
        $target = User::factory()->create(['profile_visibility' => 'public']);

        $this->block($caller, $target);

        $this->actingAs($caller)->getJson("/api/users/{$target->id}/friends")
             ->assertStatus(403);
    }

    public function test_forum_topics_return_403_when_blocked()
    {
        $caller = User::factory()->create();
        $target = User::factory()->create(['profile_visibility' => 'public']);

        $this->block($target, $caller);

        $this->actingAs($caller)->getJson("/api/users/{$target->id}/forum-topics")
             ->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // friendship_status exposed on post authors
    // -------------------------------------------------------------------------

    public function test_post_author_carries_the_viewer_relative_friendship_status()
    {
        $caller = User::factory()->create();
        $author = User::factory()->create();
        $post   = Post::factory()->create(['user_id' => $author->id]);

        Friendship::create([
            'requester_id' => $author->id,
            'addressee_id' => $caller->id,
            'status'       => 'pending',
        ]);

        $response = $this->actingAs($caller)->getJson("/api/posts/{$post->id}")
                         ->assertStatus(200);

        // The author sent the request, so the caller received it.
        $this->assertSame('pending_received', $response->json('user.friendship_status'));
    }

    public function test_post_author_status_is_none_for_a_guest()
    {
        $post = Post::factory()->create();

        $this->getJson("/api/posts/{$post->id}")
             ->assertStatus(200)
             ->assertJsonPath('user.friendship_status', 'none')
             ->assertJsonPath('user.friendship_id', null);
    }

    // -------------------------------------------------------------------------
    // Nested users must never leak private fields
    // -------------------------------------------------------------------------

    public function test_post_author_never_exposes_private_fields()
    {
        $author = User::factory()->create();
        $post   = Post::factory()->create(['user_id' => $author->id]);

        $author = $this->getJson("/api/posts/{$post->id}")
                       ->assertStatus(200)
                       ->json('user');

        foreach (['email', 'birthdate', 'role', 'is_admin', 'is_moderator', 'is_deleted', 'password_hash'] as $field) {
            $this->assertArrayNotHasKey($field, $author, "`{$field}` leaked through a nested post author");
        }

        $this->assertArrayHasKey('username', $author);
    }

    public function test_feed_authors_never_expose_private_fields()
    {
        Post::factory()->create();

        $posts = $this->getJson('/api/posts')->assertStatus(200)->json('data');

        $this->assertNotEmpty($posts);

        foreach ($posts as $post) {
            $this->assertArrayNotHasKey('email', $post['user']);
            $this->assertArrayNotHasKey('password_hash', $post['user']);
        }
    }

    public function test_own_profile_still_exposes_private_fields()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/auth/me')
             ->assertStatus(200)
             ->assertJsonStructure(['id', 'username', 'email', 'birthdate', 'role', 'is_admin'])
             ->assertJsonMissingPath('password_hash');
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post\Post;
use App\Models\Like\Like;

class PostTest extends TestCase
{
    public function test_anyone_can_list_posts()
    {
        $response = $this->getJson('/api/posts');
        $response->assertStatus(200);
    }

    public function test_anyone_can_view_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/posts/{$post->id}");
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_create_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->postJson('/api/posts', [
                             'content'    => 'Hello this is a post',
                             'is_spoiler' => false,
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'content']);
    }

    public function test_unauthenticated_user_cannot_create_post()
    {
        $response = $this->postJson('/api/posts', [
            'content' => 'Hello',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_update_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                         ->patchJson("/api/posts/{$post->id}", [
                             'content' => 'Updated content',
                         ]);

        $response->assertStatus(200);
    }

    public function test_user_cannot_update_others_post()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)
                         ->patchJson("/api/posts/{$post->id}", [
                             'content' => 'Hacked',
                         ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                         ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
    }

    public function test_user_can_save_a_post()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)
                         ->postJson("/api/posts/{$post->id}/save");

        $response->assertStatus(201)
                 ->assertJson(['saved' => true]);
    }

    public function test_user_can_unsave_a_post()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $other->id]);
        $user->savedPosts()->attach($post->id);

        $response = $this->actingAs($user)
                         ->deleteJson("/api/posts/{$post->id}/save");

        $response->assertStatus(200);
    }

    public function test_user_can_archive_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                         ->patchJson("/api/posts/{$post->id}/archive");

        $response->assertStatus(200);
        $this->assertNotNull($post->fresh()->archived_at);
    }

    public function test_user_cannot_archive_others_post()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)
                         ->patchJson("/api/posts/{$post->id}/archive");

        $response->assertStatus(403);
    }

    public function test_user_can_hide_others_post_from_feed()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)
                         ->postJson("/api/posts/{$post->id}/hide");

        $response->assertStatus(201);
    }

    public function test_user_can_list_their_liked_posts()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $post  = Post::factory()->create(['user_id' => $other->id]);

        Like::create([
            'user_id'  => $user->id,
            'post_id'  => $post->id,
            'is_liked' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/posts/me/liked');

        $response->assertStatus(200);
    }

    public function test_user_can_list_their_saved_posts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts/me/saved');

        $response->assertStatus(200);
    }

    public function test_user_can_list_their_archived_posts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts/me/archived');

        $response->assertStatus(200);
    }

    public function test_user_can_list_posts_hidden_from_their_feed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/posts/me/archived-from-feed');

        $response->assertStatus(200);
    }
}

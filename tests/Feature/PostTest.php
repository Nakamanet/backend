<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post\Post;

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
}

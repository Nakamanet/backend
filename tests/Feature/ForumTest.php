<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Forum\ForumTopic;

class ForumTest extends TestCase
{
    public function test_anyone_can_list_topics()
    {
        $response = $this->getJson('/api/forum/topics');
        $response->assertStatus(200);
    }

    public function test_anyone_can_view_a_topic()
    {
        $user  = User::factory()->create();
        $topic = ForumTopic::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/api/forum/topics/{$topic->id}");
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_create_topic()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->postJson('/api/forum/topics', [
                             'title'    => 'Best anime of 2026?',
                             'content'  => 'Share your favorites!',
                             'category' => 'anime',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'title']);
    }

    public function test_unauthenticated_user_cannot_create_topic()
    {
        $response = $this->postJson('/api/forum/topics', [
            'title'    => 'Test',
            'content'  => 'Test',
            'category' => 'general',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_reply_to_topic()
    {
        $user  = User::factory()->create();
        $topic = ForumTopic::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                         ->postJson("/api/forum/topics/{$topic->id}/reply", [
                             'content' => 'Great topic!',
                         ]);

        $response->assertStatus(201);
    }

    public function test_user_cannot_reply_to_locked_topic()
    {
        $user  = User::factory()->create();
        $topic = ForumTopic::factory()->create([
            'user_id'   => $user->id,
            'is_locked' => true,
        ]);

        $response = $this->actingAs($user)
                         ->postJson("/api/forum/topics/{$topic->id}/reply", [
                             'content' => 'trying to reply',
                         ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_topic()
    {
        $user  = User::factory()->create();
        $topic = ForumTopic::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                         ->deleteJson("/api/forum/topics/{$topic->id}");

        $response->assertStatus(200);
    }
}

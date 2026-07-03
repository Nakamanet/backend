<?php

namespace Tests\Feature;

use App\Models\Friendship\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FriendshipTest extends TestCase
{
    use DatabaseTransactions;

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function createFriendship(array $attributes = []): Friendship
    {
        return Friendship::create(array_merge([
            'status' => 'pending',
        ], $attributes));
    }

    // -------------------------------------------------------------------------
    // GET /api/friends/pending
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_list_pending_friends()
    {
        $this->getJson('/api/friends/pending')
             ->assertStatus(401);
    }

    public function test_pending_returns_only_requests_received_by_the_user()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $third = User::factory()->create();

        $received = $this->createFriendship(['requester_id' => $other->id, 'addressee_id' => $user->id]);
        // Sent by the user, must NOT appear in /pending
        $this->createFriendship(['requester_id' => $user->id, 'addressee_id' => $third->id]);
        // Belongs to other users entirely, must NOT appear
        $this->createFriendship(['requester_id' => $third->id, 'addressee_id' => $other->id]);

        $this->be($user, 'api');

        $this->getJson('/api/friends/pending')
             ->assertOk()
             ->assertJsonCount(1)
             ->assertJsonFragment(['id' => $received->id]);
    }

    public function test_pending_returns_empty_when_no_requests_received()
    {
        $this->be(User::factory()->create(), 'api');

        $this->getJson('/api/friends/pending')
             ->assertOk()
             ->assertJsonCount(0);
    }

    // -------------------------------------------------------------------------
    // GET /api/friends/sent
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_list_sent_friends()
    {
        $this->getJson('/api/friends/sent')
             ->assertStatus(401);
    }

    public function test_sent_returns_only_requests_sent_by_the_user()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $third = User::factory()->create();

        $sent = $this->createFriendship(['requester_id' => $user->id, 'addressee_id' => $other->id]);
        // Received by the user, must NOT appear in /sent
        $this->createFriendship(['requester_id' => $other->id, 'addressee_id' => $user->id]);
        // Belongs to other users entirely, must NOT appear
        $this->createFriendship(['requester_id' => $other->id, 'addressee_id' => $third->id]);

        $this->be($user, 'api');

        $this->getJson('/api/friends/sent')
             ->assertOk()
             ->assertJsonCount(1)
             ->assertJsonFragment(['id' => $sent->id])
             ->assertJsonStructure([
                 '*' => [
                     'id',
                     'requester_id',
                     'addressee_id',
                     'status',
                     'requester' => ['id', 'username', 'avatar_url'],
                     'addressee' => ['id', 'username', 'avatar_url'],
                 ],
             ]);
    }

    public function test_sent_does_not_include_accepted_or_blocked_relationships()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $third = User::factory()->create();

        $this->createFriendship(['requester_id' => $user->id, 'addressee_id' => $other->id, 'status' => 'accepted']);
        $this->createFriendship(['requester_id' => $user->id, 'addressee_id' => $third->id, 'status' => 'blocked']);

        $this->be($user, 'api');

        $this->getJson('/api/friends/sent')
             ->assertOk()
             ->assertJsonCount(0);
    }

    public function test_sent_returns_empty_when_no_requests_sent()
    {
        $this->be(User::factory()->create(), 'api');

        $this->getJson('/api/friends/sent')
             ->assertOk()
             ->assertJsonCount(0);
    }

    // -------------------------------------------------------------------------
    // GET /api/friends/blocked
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_list_blocked_friends()
    {
        $this->getJson('/api/friends/blocked')
             ->assertStatus(401);
    }

    public function test_blocked_returns_only_users_blocked_by_the_user()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $third = User::factory()->create();

        $blocked = $this->createFriendship(['requester_id' => $user->id, 'addressee_id' => $other->id, 'status' => 'blocked']);
        // The user was blocked by someone else, must NOT appear in /blocked
        $this->createFriendship(['requester_id' => $third->id, 'addressee_id' => $user->id, 'status' => 'blocked']);
        // Belongs to other users entirely, must NOT appear
        $this->createFriendship(['requester_id' => $other->id, 'addressee_id' => $third->id, 'status' => 'blocked']);

        $this->be($user, 'api');

        $this->getJson('/api/friends/blocked')
             ->assertOk()
             ->assertJsonCount(1)
             ->assertJsonFragment(['id' => $blocked->id])
             ->assertJsonStructure([
                 '*' => [
                     'id',
                     'requester_id',
                     'addressee_id',
                     'status',
                     'requester' => ['id', 'username', 'avatar_url'],
                     'addressee' => ['id', 'username', 'avatar_url'],
                 ],
             ]);
    }

    public function test_blocked_does_not_include_pending_or_accepted_relationships()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $third = User::factory()->create();

        $this->createFriendship(['requester_id' => $user->id, 'addressee_id' => $other->id, 'status' => 'pending']);
        $this->createFriendship(['requester_id' => $user->id, 'addressee_id' => $third->id, 'status' => 'accepted']);

        $this->be($user, 'api');

        $this->getJson('/api/friends/blocked')
             ->assertOk()
             ->assertJsonCount(0);
    }

    public function test_blocked_returns_empty_when_no_users_blocked()
    {
        $this->be(User::factory()->create(), 'api');

        $this->getJson('/api/friends/blocked')
             ->assertOk()
             ->assertJsonCount(0);
    }
}

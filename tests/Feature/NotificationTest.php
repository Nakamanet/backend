<?php

namespace Tests\Feature;

use App\Models\Notification\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function createNotification(array $attributes = []): Notification
    {
        return Notification::create(array_merge([
            'type'    => 'like_post',
            'is_read' => false,
            'payload' => null,
        ], $attributes));
    }

    // -------------------------------------------------------------------------
    // GET /api/notifications
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_list_notifications()
    {
        $this->getJson('/api/notifications')
             ->assertStatus(401);
    }

    public function test_authenticated_user_gets_only_their_notifications()
    {
        $user   = User::factory()->create();
        $sender = User::factory()->create();
        $other  = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id]);
        }
        // This one belongs to $other and must NOT appear
        $this->createNotification(['recipient_id' => $other->id, 'sender_id' => $sender->id]);

        $this->be($user, 'api');

        $this->getJson('/api/notifications')
             ->assertOk()
             ->assertJsonCount(3, 'data')
             ->assertJsonStructure([
                 'data' => [
                     '*' => [
                         'id',
                         'recipient_id',
                         'sender_id',
                         'type',
                         'is_read',
                         'payload',
                         'sender' => ['id', 'username', 'avatar_url'],
                     ],
                 ],
                 'current_page',
                 'per_page',
                 'total',
             ]);
    }

    public function test_notifications_are_ordered_newest_first()
    {
        $user   = User::factory()->create();
        $sender = User::factory()->create();

        $first  = $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id]);
        sleep(1); // ensure different created_at timestamps
        $second = $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id]);

        $this->be($user, 'api');

        $ids = $this->getJson('/api/notifications')
                    ->assertOk()
                    ->json('data.*.id');

        $this->assertEquals([$second->id, $first->id], $ids);
    }

    public function test_index_returns_empty_when_user_has_no_notifications()
    {
        $this->be(User::factory()->create(), 'api');

        $this->getJson('/api/notifications')
             ->assertOk()
             ->assertJsonCount(0, 'data');
    }

    // -------------------------------------------------------------------------
    // GET /api/notifications/unread-count
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_get_unread_count()
    {
        $this->getJson('/api/notifications/unread-count')
             ->assertStatus(401);
    }

    public function test_unread_count_returns_correct_number()
    {
        $user   = User::factory()->create();
        $sender = User::factory()->create();

        $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => false]);
        $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => false]);
        $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => true]);

        $this->be($user, 'api');

        $this->getJson('/api/notifications/unread-count')
             ->assertOk()
             ->assertJson(['count' => 2]);
    }

    public function test_unread_count_is_zero_when_all_notifications_are_read()
    {
        $user   = User::factory()->create();
        $sender = User::factory()->create();

        $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => true]);

        $this->be($user, 'api');

        $this->getJson('/api/notifications/unread-count')
             ->assertOk()
             ->assertJson(['count' => 0]);
    }

    public function test_unread_count_does_not_include_other_users_notifications()
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $sender = User::factory()->create();

        $this->createNotification(['recipient_id' => $other->id, 'sender_id' => $sender->id, 'is_read' => false]);

        $this->be($user, 'api');

        $this->getJson('/api/notifications/unread-count')
             ->assertOk()
             ->assertJson(['count' => 0]);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/notifications/{id}/read
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_mark_notification_as_read()
    {
        $user   = User::factory()->create();
        $sender = User::factory()->create();
        $notif  = $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id]);

        $this->patchJson("/api/notifications/{$notif->id}/read")
             ->assertStatus(401);
    }

    public function test_user_can_mark_their_own_notification_as_read()
    {
        $user   = User::factory()->create();
        $sender = User::factory()->create();
        $notif  = $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => false]);

        $this->be($user, 'api');

        $this->patchJson("/api/notifications/{$notif->id}/read")
             ->assertOk()
             ->assertJson(['message' => 'Notification marked as read']);

        $this->assertDatabaseHas('notifications', ['id' => $notif->id, 'is_read' => true]);
    }

    public function test_user_cannot_mark_another_users_notification_as_read()
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $sender = User::factory()->create();
        $notif  = $this->createNotification(['recipient_id' => $other->id, 'sender_id' => $sender->id, 'is_read' => false]);

        $this->be($user, 'api');

        $this->patchJson("/api/notifications/{$notif->id}/read")
             ->assertStatus(404);

        $this->assertDatabaseHas('notifications', ['id' => $notif->id, 'is_read' => false]);
    }

    public function test_marking_nonexistent_notification_returns_404()
    {
        $this->be(User::factory()->create(), 'api');

        $this->patchJson('/api/notifications/99999/read')
             ->assertStatus(404);
    }

    public function test_marking_already_read_notification_returns_ok()
    {
        $user   = User::factory()->create();
        $sender = User::factory()->create();
        $notif  = $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => true]);

        $this->be($user, 'api');

        $this->patchJson("/api/notifications/{$notif->id}/read")
             ->assertOk()
             ->assertJson(['message' => 'Notification marked as read']);
    }

    // -------------------------------------------------------------------------
    // PATCH /api/notifications/read-all
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_mark_all_as_read()
    {
        $this->patchJson('/api/notifications/read-all')
             ->assertStatus(401);
    }

    public function test_user_can_mark_all_their_notifications_as_read()
    {
        $user   = User::factory()->create();
        $sender = User::factory()->create();

        $n1 = $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => false]);
        $n2 = $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => false]);

        $this->be($user, 'api');

        $this->patchJson('/api/notifications/read-all')
             ->assertOk()
             ->assertJson(['message' => 'All notifications marked as read']);

        $this->assertDatabaseHas('notifications', ['id' => $n1->id, 'is_read' => true]);
        $this->assertDatabaseHas('notifications', ['id' => $n2->id, 'is_read' => true]);
    }

    public function test_mark_all_as_read_does_not_affect_other_users()
    {
        $user       = User::factory()->create();
        $other      = User::factory()->create();
        $sender     = User::factory()->create();
        $otherNotif = $this->createNotification(['recipient_id' => $other->id, 'sender_id' => $sender->id, 'is_read' => false]);

        $this->be($user, 'api');
        $this->patchJson('/api/notifications/read-all')->assertOk();

        $this->assertDatabaseHas('notifications', ['id' => $otherNotif->id, 'is_read' => false]);
    }

    public function test_mark_all_as_read_is_idempotent_when_nothing_unread()
    {
        $this->be(User::factory()->create(), 'api');

        $this->patchJson('/api/notifications/read-all')
             ->assertOk()
             ->assertJson(['message' => 'All notifications marked as read']);
    }

    public function test_unread_count_is_zero_after_mark_all_as_read()
    {
        $user   = User::factory()->create();
        $sender = User::factory()->create();

        $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => false]);
        $this->createNotification(['recipient_id' => $user->id, 'sender_id' => $sender->id, 'is_read' => false]);

        $this->be($user, 'api');

        $this->patchJson('/api/notifications/read-all')->assertOk();

        $this->getJson('/api/notifications/unread-count')
             ->assertJson(['count' => 0]);
    }
}

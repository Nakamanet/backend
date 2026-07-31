<?php

namespace App\Models\Friendship;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Friendship extends Model
{
    use HasFactory;

    protected $table = 'Friendships';
    public $timestamps = false;

    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee()
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }

    /**
     * The relationship row between two users, whichever side initiated it.
     */
    public static function between(int $userId, int $otherId): ?self
    {
        return static::where(fn($q) => $q->where('requester_id', $userId)->where('addressee_id', $otherId))
            ->orWhere(fn($q) => $q->where('requester_id', $otherId)->where('addressee_id', $userId))
            ->first();
    }

    /**
     * Whether one of the two users has blocked the other (either direction).
     */
    public static function isBlockedBetween(int $userId, int $otherId): bool
    {
        if ($userId === $otherId) {
            return false;
        }

        $friendship = static::between($userId, $otherId);

        return $friendship !== null && $friendship->status === 'blocked';
    }

    /**
     * Ids of every user in a blocked relationship with $userId, both directions.
     */
    public static function blockedUserIdsFor(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return static::whereRaw("status = 'blocked'")
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('addressee_id', $userId);
            })
            ->get()
            ->map(fn($f) => $f->requester_id === $userId ? $f->addressee_id : $f->requester_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Viewer-relative label for a relationship row: what $viewerId sees.
     *
     * `blocked` = the viewer blocked the other user (can unblock),
     * `blocked_by` = the viewer was blocked (no action possible).
     */
    public static function labelFor(?self $friendship, int $viewerId): string
    {
        if (! $friendship) {
            return 'none';
        }

        return match (true) {
            $friendship->status === 'accepted' => 'friends',
            $friendship->status === 'pending' && $friendship->requester_id === $viewerId => 'pending_sent',
            $friendship->status === 'pending' => 'pending_received',
            $friendship->status === 'blocked' && $friendship->requester_id === $viewerId => 'blocked',
            $friendship->status === 'blocked' => 'blocked_by',
            default => 'none',
        };
    }

    /**
     * Viewer-relative status + relationship id towards a single user.
     *
     * @return array{friendship_status: string, friendship_id: int|null}
     */
    public static function statusFor(?int $viewerId, int $targetId): array
    {
        if (! $viewerId || $viewerId === $targetId) {
            return ['friendship_status' => 'none', 'friendship_id' => null];
        }

        $friendship = static::between($viewerId, $targetId);

        return [
            'friendship_status' => static::labelFor($friendship, $viewerId),
            'friendship_id'     => $friendship?->id,
        ];
    }

    /**
     * Same as statusFor() for many targets at once, in a single query.
     * Targets with no relationship row are absent from the map.
     *
     * @param  array<int>  $targetIds
     * @return array<int, array{friendship_status: string, friendship_id: int|null}>
     */
    public static function statusMapFor(?int $viewerId, array $targetIds): array
    {
        $targetIds = array_values(array_unique(array_filter($targetIds)));

        if (! $viewerId || empty($targetIds)) {
            return [];
        }

        $rows = static::where(function ($q) use ($viewerId, $targetIds) {
                $q->where('requester_id', $viewerId)->whereIn('addressee_id', $targetIds);
            })
            ->orWhere(function ($q) use ($viewerId, $targetIds) {
                $q->where('addressee_id', $viewerId)->whereIn('requester_id', $targetIds);
            })
            ->get();

        $map = [];

        foreach ($rows as $friendship) {
            $otherId = $friendship->requester_id === $viewerId
                ? $friendship->addressee_id
                : $friendship->requester_id;

            $map[$otherId] = [
                'friendship_status' => static::labelFor($friendship, $viewerId),
                'friendship_id'     => $friendship->id,
            ];
        }

        return $map;
    }
}

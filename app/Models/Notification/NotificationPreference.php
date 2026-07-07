<?php

namespace App\Models\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'comment',
        'friends_requests',
        'mentions',
        'new_sorties',
        'messages',
    ];

    protected $casts = [
        'comment' => 'boolean',
        'friends_requests' => 'boolean',
        'mentions' => 'boolean',
        'new_sorties' => 'boolean',
        'messages' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

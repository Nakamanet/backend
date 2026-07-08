<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Forum\ForumUserPin;

class ForumTopic extends Model
{
    use HasFactory;

    protected $table = 'Forum_Topics';

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'content',
        'related_anime_id',
        'related_manga_id',
        'is_pinned',
        'is_locked',
        'is_archived',
        'views_count',
        'votes_count',
    ];

    protected $casts = [
        'is_pinned'    => 'boolean',
        'is_locked'    => 'boolean',
        'is_archived'  => 'boolean',
        'views_count'  => 'integer',
        'votes_count'  => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies()
    {
        return $this->hasMany(ForumReply::class, 'topic_id');
    }

    public function userPins()
    {
        return $this->hasMany(ForumUserPin::class, 'topic_id');
    }


    protected static function newFactory()
    {
        return \Database\Factories\ForumTopicFactory::new();
    }
}

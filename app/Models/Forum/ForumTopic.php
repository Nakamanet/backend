<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ForumTopic extends Model
{
    use HasFactory;

    protected $table = 'Forum_Topics';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'content',
        'related_anime_id',
        'related_manga_id',
        'is_pinned',
        'is_locked',
    ];

    protected $casts = [
        'is_pinned'  => 'boolean',
        'is_locked'  => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies()
    {
        return $this->hasMany(ForumReply::class, 'topic_id');
    }
}

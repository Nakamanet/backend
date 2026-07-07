<?php

namespace App\Models\Post;

use App\Models\User;
use App\Models\Like\Like;
use App\Models\Comment\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $table = 'Posts';

    protected $fillable = [
        'user_id',
        'related_anime_id',
        'related_manga_id',
        'content',
        'image_urls',
        'is_spoiler',
        'archived_at',
    ];

    protected $casts = [
        'image_urls'  => 'array',
        'is_spoiler'  => 'boolean',
        'created_at'  => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class, 'post_id');
    }

    public function savedBy()
    {
        return $this->belongsToMany(User::class, 'Saved_Posts', 'post_id', 'user_id')
            ->withTimestamps();
    }

    public function archivedBy()
    {
        return $this->belongsToMany(User::class, 'Archived_Posts', 'post_id', 'user_id')
            ->withTimestamps();
    }

    public function scopeVisibleTo($query, ?int $userId)
    {
        $query->whereNull('archived_at');

        if ($userId) {
            $query->whereDoesntHave('archivedBy', fn($q) => $q->where('user_id', $userId));
        }

        return $query;
    }

    protected static function newFactory()
    {
        return \Database\Factories\PostFactory::new();
    }
}

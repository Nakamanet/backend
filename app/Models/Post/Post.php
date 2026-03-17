<?php

namespace App\Models\Post;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $table = 'Posts';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'related_anime_id',
        'related_manga_id',
        'content',
        'image_urls',
        'is_spoiler',
    ];

    protected $casts = [
        'image_urls' => 'array',
        'is_spoiler' => 'boolean',
        'created_at' => 'datetime',
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
}

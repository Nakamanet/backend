<?php

namespace App\Models\Like;

use App\Models\User;
use App\Models\Post\Post;
use App\Models\Comment\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Like extends Model
{
    use HasFactory;

    protected $table = 'Likes';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'post_id',
        'comment_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
}

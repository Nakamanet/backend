<?php

namespace App\Models\Comment;

use App\Models\User;
use App\Models\Post\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
    use HasFactory;

    protected $table = 'Comments';
    public $timestamps = false; // ← added: table has no created_at/updated_at columns

    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'content',
        'is_spoiler',
    ];

    protected $casts = [
        'is_spoiler' => 'boolean',
        // removed created_at/updated_at casts — columns don't exist
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}

<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ForumReply extends Model
{
    use HasFactory;

    protected $table = 'Forum_Replies';
    
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\HideToxicContentScope);
    }
    
    protected $fillable = [
        'topic_id',
        'user_id',
        'parent_id',
        'content',
        'is_hidden',
        'votes_count',
    ];

    protected $casts = [
        'is_hidden'   => 'boolean',
        'votes_count' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function topic()
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    public function parent()
    {
        return $this->belongsTo(ForumReply::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(ForumReply::class, 'parent_id');
    }
}

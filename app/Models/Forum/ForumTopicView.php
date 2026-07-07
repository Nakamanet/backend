<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumTopicView extends Model
{
    public $timestamps = false;

    protected $table = 'Forum_Topic_Views';

    protected $fillable = ['topic_id', 'user_id'];

    public function topic()
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

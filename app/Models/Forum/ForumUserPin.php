<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumUserPin extends Model
{
    public $timestamps = false;

    protected $table = 'Forum_User_Pins';

    protected $fillable = ['user_id', 'topic_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function topic()
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }
}

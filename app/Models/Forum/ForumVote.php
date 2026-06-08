<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumVote extends Model
{
    public $timestamps = false;

    protected $table = 'Forum_Votes';

    protected $fillable = ['user_id', 'target_type', 'target_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

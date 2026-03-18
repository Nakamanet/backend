<?php

namespace App\Models\Library;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserAnimeLibrary extends Model
{
    protected $table = 'User_Anime_Library';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'anime_id',
        'status',
        'progress',
        'rewatch_count',
        'score',
        'is_private',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

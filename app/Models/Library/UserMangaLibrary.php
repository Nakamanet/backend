<?php

namespace App\Models\Library;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserMangaLibrary extends Model
{
    protected $table = 'User_Manga_Library';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'manga_id',
        'status',
        'progress',
        'reread_count',
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

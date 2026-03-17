<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'Users';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'birthdate',
        'localisation',
        'bio',
        'avatar_url',
        'banner_url',
        'role',
        'is_deleted',
        'is_admin',
        'is_moderator',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'birthdate'    => 'date',
        'is_deleted'   => 'boolean',
        'is_admin'     => 'boolean',
        'is_moderator' => 'boolean',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function animeLibrary()
    {
        return $this->hasMany(UserAnimeLibrary::class, 'user_id');
    }

    public function mangaLibrary()
    {
        return $this->hasMany(UserMangaLibrary::class, 'user_id');
    }
}

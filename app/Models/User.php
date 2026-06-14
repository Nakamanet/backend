<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;

use App\Models\Post\Post;
use App\Models\Library\UserAnimeLibrary;
use App\Models\Library\UserMangaLibrary;
use Database\Factories\UserFactory;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable, MustVerifyEmailTrait;

    protected $table = 'Users';

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
        'birthdate'          => 'date',
        'email_verified_at'  => 'datetime',
        'is_deleted'         => 'boolean',
        'is_admin'           => 'boolean',
        'is_moderator'       => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

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

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}

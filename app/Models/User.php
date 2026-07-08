<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

use App\Models\Post\Post;
use App\Models\Library\UserAnimeLibrary;
use App\Models\Library\UserMangaLibrary;
use Database\Factories\UserFactory;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

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
        'profile_visibility',
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

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

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
        return $this->hasMany(\App\Models\Post\Post::class, 'user_id');
    }
    
    public function animeLibrary()
    {
        return $this->hasMany(\App\Models\Library\UserAnimeLibrary::class, 'user_id');
    }

    public function mangaLibrary()
    {
        return $this->hasMany(\App\Models\Library\UserMangaLibrary::class, 'user_id');
    }


    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function savedPosts()
    {
        return $this->belongsToMany(\App\Models\Post\Post::class, 'Saved_Posts', 'user_id', 'post_id')
            ->withTimestamps();
    }

    public function archivedPosts()
    {
        return $this->belongsToMany(\App\Models\Post\Post::class, 'Archived_Posts', 'user_id', 'post_id')
            ->withTimestamps();
    }
}

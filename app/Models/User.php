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

    /**
     * Fields the owner (or an admin) may see, but that must never leak through a
     * nested `user` object — post authors, comment authors, forum authors,
     * friendship sides… Every one of those relations serializes the full model.
     *
     * Hidden by default and re-exposed explicitly via withPrivateFields(), so a
     * new endpoint leaks nothing until someone opts in on purpose.
     */
    public const PRIVATE_FIELDS = [
        'email',
        'birthdate',
        'role',
        'is_admin',
        'is_moderator',
        'is_deleted',
        'profile_visibility',
    ];

    protected $hidden = [
        'password_hash',
        ...self::PRIVATE_FIELDS,
    ];

    /**
     * Re-expose PRIVATE_FIELDS on a payload that legitimately describes the
     * caller themselves (auth responses, own profile) or an admin listing.
     */
    public function withPrivateFields(): self
    {
        return $this->makeVisible(self::PRIVATE_FIELDS);
    }

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
        return [
            'is_admin' => (bool) $this->is_admin,
        ];
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

    /**
     * Whether the given viewer is allowed to see this user's private social data
     * (liked posts, friends list, …), based on `profile_visibility`.
     *
     * Mirrors the access rules of UserController::profile:
     *  - own data is always visible;
     *  - `public`       → visible to everyone (incl. guests);
     *  - `friends_only` → visible only to confirmed friends;
     *  - `private`      → visible to no one but the owner.
     */
    public function isVisibleTo(?self $viewer): bool
    {
        if ($viewer && $viewer->id === $this->id) {
            return true;
        }

        return match ($this->profile_visibility) {
            'private'      => false,
            'friends_only' => $viewer
                ? \App\Models\Friendship\Friendship::where('status', 'accepted')
                    ->where(fn($q) => $q->where('requester_id', $viewer->id)->where('addressee_id', $this->id))
                    ->orWhere(fn($q) => $q->where('requester_id', $this->id)->where('addressee_id', $viewer->id))
                    ->exists()
                : false,
            default        => true, // 'public' (ou valeur nulle)
        };
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'Users';
    public $timestamp = false;

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
        'theme_preference',
    ];

    protected $hidden = [
        'password_hash',
    ];
}

<?php

namespace App\Models\Friendship;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Friendship extends Model
{
    use HasFactory;

    protected $table = 'Friendships';
    public $timestamps = false;

    protected $fillable = [
        'requester_id',
        'addressee_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function addressee()
    {
        return $this->belongsTo(User::class, 'addressee_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail

{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    // public function vendors(): HasMany {
    //     return $this->hasMany(Vendor::class, 'owner_id');
    // }

    // public function orders(): HasMany {
    //     return $this->hasMany(Order::class);
    // }
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    public function vendors()
    {
        return $this->hasOne(Vendor::class, 'owner_id');
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
}

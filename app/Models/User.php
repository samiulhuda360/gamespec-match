<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password_hash',
        'username',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
        ];
    }

    /**
     * Get the user's wishlist items
     */
    /** The hash lives in password_hash (as in production); rehashing and resets use this name. */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get the user's hardware profiles
     */
    public function hardwareProfiles(): HasMany
    {
        return $this->hasMany(UserHardware::class);
    }

    /**
     * Get the user's default hardware profile
     */
    public function defaultHardwareProfile()
    {
        return $this->hasMany(UserHardware::class)->where('is_default', true)->first();
    }

    /**
     * Get the password for authentication (use password_hash field)
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}

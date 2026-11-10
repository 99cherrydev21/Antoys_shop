<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Role;

#[Fillable(['name', 'phone', 'password', 'role_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
   protected function casts(): array
{
    return [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];
}

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isJefe(): bool
    {
        return mb_strtolower((string) $this->role?->name) === 'jefe';
    }

    public function isInventarista(): bool
    {
        return mb_strtolower((string) $this->role?->name) === 'inventarista';
    }

    public function sales(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Sale::class);
    }

    public function isTecnico(): bool
    {
        return mb_strtolower((string) $this->role?->name) === 'tecnico';
    }

}

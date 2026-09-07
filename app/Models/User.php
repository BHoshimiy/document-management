<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'password',
        'status',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class);
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function canManageCatalog(): bool
    {
        return $this->role->canManageCatalog();
    }

    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::Active);
    }
}

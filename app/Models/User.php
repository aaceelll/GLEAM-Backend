<?php

// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nama',
        'email',
        'username',
        'nomor_telepon',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'role',
        'password',
        'email_verified_at',
    ];

    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin','admin'], true);
    }
    public function isNakes(): bool { return $this->role === 'nakes'; }
    public function isManajemen(): bool { return $this->role === 'manajemen'; }
}


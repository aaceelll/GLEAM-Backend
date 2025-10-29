<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
    'nama',
    'email',
    'username',
    'nomor_telepon',
    'tanggal_lahir',
    'umur',
    'tempat_lahir',
    'jenis_kelamin',
    'pekerjaan',
    'pendidikan_terakhir',

    'riwayat_pelayanan_kesehatan',
    'riwayat_merokok',
    'berat_badan',
    'tinggi_badan',
    'indeks_bmi',
    'riwayat_penyakit_jantung',
    'durasi_diagnosis',
    'berobat_ke_dokter',

    'has_completed_profile',
    'role',

    'password',
    'email_verified_at',
    'kelurahan',
    'rw',
    'latitude',
    'longitude',
    'address',
];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'tanggal_lahir' => 'date',
        'umur' => 'integer',
        'berat_badan' => 'decimal:2',
        'tinggi_badan' => 'decimal:2',
        'indeks_bmi' => 'decimal:2',
        'has_completed_profile' => 'boolean',
        'email_verified_at' => 'datetime', 
    ];

    public function scopePatients($query)
    {
        return $query->where('role', 'user');
    }
}

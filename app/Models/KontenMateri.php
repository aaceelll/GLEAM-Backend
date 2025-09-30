<?php
// app/Models/KontenMateri.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontenMateri extends Model
{
    protected $table = 'konten_materi';
    
    protected $fillable = [
        'materi_id',
        'judul',
        'video_id',
        'file_url',
        'deskripsi',
    ];

    public function materi()
    {
        return $this->belongsTo(Materi::class);
    }
}
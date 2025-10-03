<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankSoal extends Model
{
    protected $table = 'question_banks';
    protected $fillable = ['nama', 'status']; // sesuaikan kolommu

    public function soal()
    {
        return $this->hasMany(Soal::class, 'bank_id');
    }

    public function materi()
    {
        return $this->belongsToMany(Materi::class, 'materi_bank_soal', 'bank_id', 'materi_id')
            ->withPivot(['tipe', 'is_active', 'urutan'])
            ->withTimestamps();
    }
}

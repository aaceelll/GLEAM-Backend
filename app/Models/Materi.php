<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materi extends Model
{
    protected $table = 'materi'; 
    protected $fillable = ['nama','slug'];

    public function bankSoal()
    {
        // pivot: materi_bank_soal (materi_id, bank_id, tipe, is_active, urutan, timestamps)
        return $this->belongsToMany(BankSoal::class, 'materi_bank_soal', 'materi_id', 'bank_id')
                    ->withPivot(['tipe', 'is_active', 'urutan'])
                    ->withTimestamps();
    }
}

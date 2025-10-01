<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Soal extends Model
{
    protected $table = 'questions';

    protected $fillable = [
        'bank_id', 'teks', 'tipe', 'bobot', 'opsi', 'kunci'
    ];

    protected $casts = [
        'opsi' => 'array',
    ];

    public function bank()
    {
        return $this->belongsTo(BankSoal::class, 'bank_id');
    }
}

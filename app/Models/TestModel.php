<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model {
    protected $table = 'tests';
    protected $fillable = ['nama','tipe','materi_id','bank_id','status'];
    public function bank() { return $this->belongsTo(BankSoal::class, 'bank_id'); }
    public function materi() { return $this->belongsTo(Materi::class, 'materi_id'); }
}

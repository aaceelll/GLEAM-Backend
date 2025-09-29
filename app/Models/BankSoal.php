<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankSoal extends Model
{
    protected $table = 'question_banks';
    protected $fillable = ['nama','status'];

    public function soal() { return $this->hasMany(Soal::class, 'bank_id'); }
    public function tests() { return $this->hasMany(TestModel::class, 'bank_id'); }
}

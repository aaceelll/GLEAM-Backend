<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materi extends Model
{
    protected $table = 'materi';
    protected $fillable = ['nama','slug','deskripsi'];

    public function tests() { return $this->hasMany(TestModel::class, 'materi_id'); }
}

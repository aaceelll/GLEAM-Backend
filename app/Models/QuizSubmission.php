<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'bank_id',
        'tipe',
        'total_score',
        'max_score',
        'percentage',
        'answers',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(BankSoal::class, 'bank_id');
    }
}
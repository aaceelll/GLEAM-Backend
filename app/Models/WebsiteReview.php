<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'q1', 'q2', 'q3', 'q4', 'q5',
        'q6', 'q7', 'q8', 'q9', 'q10',
        'suggestion',
    ];

    protected $casts = [
        'q1' => 'integer',
        'q2' => 'integer',
        'q3' => 'integer',
        'q4' => 'integer',
        'q5' => 'integer',
        'q6' => 'integer',
        'q7' => 'integer',
        'q8' => 'integer',
        'q9' => 'integer',
        'q10' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;

class DiabetesScreening extends Model
{
    protected $table = 'diabetes_screenings';

    protected $fillable = [
        'patient_name',
        'user_id',
        'nakes_id',
        'age',
        'gender',
        'systolic_bp',
        'diastolic_bp',
        'heart_disease',
        'smoking_history',
        'bmi',
        'blood_glucose_level',
        'diabetes_probability',
        'diabetes_result',
        'bp_classification',
        'bp_recommendation',
        'full_result',
    ];

    protected $casts = [
        'age' => 'integer',
        'systolic_bp' => 'integer',
        'diastolic_bp' => 'integer',
        'heart_disease' => 'boolean',
        'bmi' => 'decimal:2',
        'blood_glucose_level' => 'decimal:2',
        'diabetes_probability' => 'decimal:4',
        'full_result' => 'array', // JSON
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pastikan JSON tanggal keluar dalam RFC3339 + offset TZ app (Asia/Jakarta).
     * Ini mencegah browser melakukan konversi yang bikin “mundur/maju 7 jam”.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        $tz = new DateTimeZone(config('app.timezone', 'Asia/Jakarta'));
        return Carbon::instance($date)   // pastikan tipe Carbon
            ->setTimezone($tz)           // set offset Asia/Jakarta
            ->toAtomString();            // RFC3339: 2025-10-10T21:28:00+07:00
    }

}

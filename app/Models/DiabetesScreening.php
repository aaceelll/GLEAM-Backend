<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiabetesScreening extends Model
{
    protected $table = 'diabetes_screenings';

    protected $fillable = [
        'patient_name','user_id','nakes_id','age','gender',
        'systolic_bp','diastolic_bp','heart_disease','smoking_history',
        'bmi','blood_glucose_level','diabetes_probability',
    ];

    protected $casts = [
        'age' => 'integer',
        'systolic_bp' => 'float',
        'diastolic_bp' => 'float',
        'bmi' => 'float',
        'blood_glucose_level' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

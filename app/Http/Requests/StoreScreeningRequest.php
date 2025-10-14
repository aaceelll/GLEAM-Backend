<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScreeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        // true jika public; kalau butuh auth, ganti ke auth()->check()
        return true;
    }

    public function rules(): array
    {
        return [
            'patientName'           => ['required', 'string', 'max:255'],
            'userId'                => ['nullable', 'integer'],
            'nakesId'               => ['nullable', 'integer'],

            'age'                   => ['nullable', 'integer', 'min:0', 'max:150'],
            'gender'                => ['nullable', 'string', 'max:16'],

            'systolic_bp'           => ['nullable', 'integer', 'min:0', 'max:300'],
            'diastolic_bp'          => ['nullable', 'integer', 'min:0', 'max:200'],
            'heart_disease'         => ['nullable', 'boolean'],
            'smoking_history'       => ['nullable', 'string', 'max:64'],

            'bmi'                   => ['nullable', 'numeric', 'min:0', 'max:200'],
            'blood_glucose_level'   => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'diabetes_probability'  => ['nullable', 'numeric', 'min:0', 'max:1'],
            'diabetes_result'       => ['nullable', 'string', 'max:64'],

            'bp_classification'     => ['nullable', 'string', 'max:64'],
            'bp_recommendation'     => ['nullable', 'string', 'max:255'],

            'full_result'           => ['nullable', 'array'], // JSON object
        ];
    }

    public function messages(): array
    {
        return [
            'patientName.required' => 'Nama pasien wajib diisi.',
        ];
    }
}

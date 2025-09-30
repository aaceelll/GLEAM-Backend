<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diabetes_screenings', function (Blueprint $table) {
            $table->id();
            $table->string('patient_name');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('nakes_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Data input
            $table->integer('age')->nullable();
            $table->string('gender', 20)->nullable();
            $table->decimal('systolic_bp', 5, 2)->nullable();
            $table->decimal('diastolic_bp', 5, 2)->nullable();
            $table->string('heart_disease', 10)->nullable();
            $table->string('smoking_history', 100)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->decimal('blood_glucose_level', 6, 2)->nullable();
            
            // Hasil prediksi
            $table->string('diabetes_probability', 20)->nullable();
            $table->text('diabetes_result')->nullable();
            $table->string('bp_classification', 100)->nullable();
            $table->text('bp_recommendation')->nullable();
            $table->text('full_result')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('nakes_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diabetes_screenings');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_id')->constrained('question_banks')->onDelete('cascade');
            $table->string('tipe'); // 'pre' atau 'post'
            $table->integer('total_score')->default(0); // Skor total
            $table->integer('max_score')->default(0); // Skor maksimal
            $table->decimal('percentage', 5, 2)->default(0); // Persentase (misal: 85.50)
            $table->json('answers'); // Simpan jawaban user: {"soal_id": "opsi_no"}
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['user_id', 'bank_id', 'tipe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_submissions');
    }
};
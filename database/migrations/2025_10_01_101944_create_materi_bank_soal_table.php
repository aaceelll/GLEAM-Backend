// database/migrations/2025_10_01_000002_create_materi_bank_soal_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materi_bank_soal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('materi_id');
            $table->unsignedBigInteger('bank_id');
            $table->string('tipe', 20)->default('pre'); // pre|post|kuis
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();

            $table->unique(['materi_id','bank_id','tipe']);
            $table->foreign('materi_id')->references('id')->on('materi')->cascadeOnDelete();
            $table->foreign('bank_id')->references('id')->on('question_banks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materi_bank_soal');
    }
};

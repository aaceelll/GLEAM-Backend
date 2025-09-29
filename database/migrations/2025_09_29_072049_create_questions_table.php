<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bank_id')->constrained('question_banks')->cascadeOnDelete();
            $t->text('teks');
            $t->enum('tipe', ['true_false','pilihan_ganda']);
            $t->json('opsi')->nullable();   // untuk pilihan ganda
            $t->string('kunci')->nullable(); // 'true'/'false' atau huruf opsi
            $t->unsignedInteger('bobot')->default(1);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('questions'); }
};

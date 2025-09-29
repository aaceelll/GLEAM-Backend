<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tests', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->enum('tipe', ['pre','post']);
            $t->foreignId('materi_id')->constrained('materi')->cascadeOnDelete();
            $t->foreignId('bank_id')->constrained('question_banks')->cascadeOnDelete();
            $t->enum('status', ['draft','publish'])->default('draft');
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tests'); }
};

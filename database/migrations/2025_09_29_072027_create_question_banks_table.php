<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('question_banks', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->enum('status', ['draft','publish'])->default('draft');
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('question_banks'); }
};

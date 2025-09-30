<?php
// database/migrations/xxxx_create_konten_materi_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('konten_materi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('materi_id')->constrained('materi')->onDelete('cascade');
            $t->string('judul');
            $t->string('video_id')->nullable();
            $t->string('file_url');
            $t->text('deskripsi');
            $t->timestamps();
        });
    }
    
    public function down(): void { 
        Schema::dropIfExists('konten_materi'); 
    }
};
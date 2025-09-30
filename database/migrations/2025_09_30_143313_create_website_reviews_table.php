<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('website_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // 10 pertanyaan dengan skala 1-5
            $table->integer('q1')->nullable(); // Saya ingin menggunakan website ini secara rutin
            $table->integer('q2')->nullable(); // Website ini terasa tidak perlu rumit
            $table->integer('q3')->nullable(); // Website ini mudah digunakan
            $table->integer('q4')->nullable(); // Saya merasa membutuhkan bantuan teknis untuk bisa menggunakan website ini
            $table->integer('q5')->nullable(); // Fitur-fitur pada website ini terintegrasi dengan baik
            $table->integer('q6')->nullable(); // Website ini terasa tidak konsisten
            $table->integer('q7')->nullable(); // Saya yakin orang lain dapat belajar menggunakan website ini dengan cepat
            $table->integer('q8')->nullable(); // Website ini terasa membingungkan atau canggung
            $table->integer('q9')->nullable(); // Saya percaya diri menggunakan website ini
            $table->integer('q10')->nullable(); // Saya perlu mempelajari banyak hal sebelum dapat menggunakan website ini
            
            // Kotak saran
            $table->text('suggestion')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_reviews');
    }
};
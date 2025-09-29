<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan perubahan pada tabel users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Pastikan kolom "phone" memang ada sebelum di-drop
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }

    /**
     * Batalkan perubahan (rollback).
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kalau di-rollback, tambahkan kembali kolom phone (optional)
            $table->string('phone')->nullable();
        });
    }
};

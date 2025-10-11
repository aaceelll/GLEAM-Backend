<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('tanggal_lahir')->nullable()->change();
            $table->string('jenis_kelamin')->nullable()->change();
            $table->string('alamat')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('tanggal_lahir')->nullable(false)->change();
            $table->string('jenis_kelamin')->nullable(false)->change();
            $table->string('alamat')->nullable(false)->change();
        });
    }
};
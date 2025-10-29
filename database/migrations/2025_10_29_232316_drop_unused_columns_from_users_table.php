<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'lama_terdiagnosis')) {
                $table->dropColumn('lama_terdiagnosis');
            }
            if (Schema::hasColumn('users', 'sudah_berobat')) {
                $table->dropColumn('sudah_berobat');
            }
            if (Schema::hasColumn('users', 'alamat')) {
                $table->dropColumn('alamat');
            }
            if (Schema::hasColumn('users', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
            if (Schema::hasColumn('users', 'diagnosa_medis')) {
                $table->dropColumn('diagnosa_medis');
            }
            if (Schema::hasColumn('users', 'riwayat_kesehatan')) {
                $table->dropColumn('riwayat_kesehatan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'lama_terdiagnosis')) {
                $table->string('lama_terdiagnosis')->nullable();
            }
            if (!Schema::hasColumn('users', 'sudah_berobat')) {
                $table->string('sudah_berobat')->nullable();
            }
            if (!Schema::hasColumn('users', 'alamat')) {
                $table->string('alamat')->nullable();
            }
            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken()->nullable();
            }
            if (!Schema::hasColumn('users', 'diagnosa_medis')) {
                $table->string('diagnosa_medis')->nullable();
            }
            if (!Schema::hasColumn('users', 'riwayat_kesehatan')) {
                $table->string('riwayat_kesehatan')->nullable();
            }
        });
    }
};

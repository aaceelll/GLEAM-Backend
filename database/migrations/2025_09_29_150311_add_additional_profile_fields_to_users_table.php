<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tempat_lahir')) {
                $table->string('tempat_lahir')->nullable();
            }
            if (!Schema::hasColumn('users', 'riwayat_kesehatan')) {
                $table->string('riwayat_kesehatan')->nullable();
            }
            if (!Schema::hasColumn('users', 'durasi_diagnosis')) {
                $table->string('durasi_diagnosis')->nullable();
            }
            if (!Schema::hasColumn('users', 'berobat_ke_dokter')) {
                $table->string('berobat_ke_dokter')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tempat_lahir', 'riwayat_kesehatan', 'durasi_diagnosis', 'berobat_ke_dokter']);
        });
    }
};

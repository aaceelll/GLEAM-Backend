<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom baru untuk personal information
            if (!Schema::hasColumn('users', 'umur')) {
                $table->integer('umur')->nullable()->after('tanggal_lahir');
            }
            if (!Schema::hasColumn('users', 'pekerjaan')) {
                $table->string('pekerjaan')->nullable()->after('umur');
            }
            if (!Schema::hasColumn('users', 'pendidikan_terakhir')) {
                $table->string('pendidikan_terakhir')->nullable()->after('pekerjaan');
            }
            if (!Schema::hasColumn('users', 'riwayat_pelayanan_kesehatan')) {
                $table->string('riwayat_pelayanan_kesehatan')->nullable()->after('pendidikan_terakhir');
            }
            if (!Schema::hasColumn('users', 'riwayat_merokok')) {
                $table->enum('riwayat_merokok', ['Perokok Aktif', 'Mantan Perokok', 'Tidak Pernah Merokok', 'Tidak Ada Informasi'])->nullable()->after('riwayat_pelayanan_kesehatan');
            }
            if (!Schema::hasColumn('users', 'berat_badan')) {
                $table->decimal('berat_badan', 5, 2)->nullable()->after('riwayat_merokok'); // kg
            }
            if (!Schema::hasColumn('users', 'tinggi_badan')) {
                $table->decimal('tinggi_badan', 5, 2)->nullable()->after('berat_badan'); // cm
            }
            if (!Schema::hasColumn('users', 'indeks_bmi')) {
                $table->decimal('indeks_bmi', 5, 2)->nullable()->after('tinggi_badan');
            }
            if (!Schema::hasColumn('users', 'riwayat_penyakit_jantung')) {
                $table->enum('riwayat_penyakit_jantung', ['Ya', 'Tidak'])->nullable()->after('indeks_bmi');
            }
            if (!Schema::hasColumn('users', 'lama_terdiagnosis')) {
                $table->string('lama_terdiagnosis')->nullable()->after('riwayat_penyakit_jantung');
            }
            if (!Schema::hasColumn('users', 'sudah_berobat')) {
                $table->enum('sudah_berobat', ['Sudah', 'Belum Pernah'])->nullable()->after('lama_terdiagnosis');
            }
            if (!Schema::hasColumn('users', 'diagnosa_medis')) {
                $table->string('diagnosa_medis')->nullable()->after('role');
            }
            // Flag untuk menandai user sudah mengisi personal info atau belum
            if (!Schema::hasColumn('users', 'has_completed_profile')) {
                $table->boolean('has_completed_profile')->default(false)->after('sudah_berobat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'umur', 'pekerjaan', 'pendidikan_terakhir', 'riwayat_pelayanan_kesehatan',
                'riwayat_merokok', 'berat_badan', 'tinggi_badan', 'indeks_bmi',
                'riwayat_penyakit_jantung', 'lama_terdiagnosis', 'sudah_berobat',
                'diagnosa_medis', 'has_completed_profile'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
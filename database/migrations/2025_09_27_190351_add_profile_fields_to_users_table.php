<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom yang sudah kamu pakai di FE & controller
            if (!Schema::hasColumn('users', 'nama')) {
                $table->string('nama')->after('id');
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'nomor_telepon')) {
                $table->string('nomor_telepon')->after('username');
            }

            // Tambahkan tiga kolom berikut agar semua input form tersimpan
            if (!Schema::hasColumn('users', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('nomor_telepon');
            }
            if (!Schema::hasColumn('users', 'jenis_kelamin')) {
                $table->enum('jenis_kelamin', ['male','female'])->nullable()->after('tanggal_lahir');
            }
            if (!Schema::hasColumn('users', 'alamat')) {
                $table->string('alamat', 255)->nullable()->after('jenis_kelamin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_lahir',
                'jenis_kelamin',
                'alamat',
                // hapus yang lain hanya kalau kamu juga ingin rollback-nya:
                // 'nama','username','nomor_telepon',
            ]);
        });
    }
};

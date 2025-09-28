<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Cek dan tambahkan kolom hanya jika belum ada
            if (!Schema::hasColumn('users', 'nama_lengkap')) {
                $table->string('nama_lengkap')->after('id'); // Gunakan 'id' sebagai referensi
            }
            
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('nama_lengkap');
            }
            
            if (!Schema::hasColumn('users', 'nomor_telepon')) {
                $table->string('nomor_telepon')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'nakes', 'manajemen'])->default('admin');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['nama_lengkap', 'username', 'nomor_telepon', 'role'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
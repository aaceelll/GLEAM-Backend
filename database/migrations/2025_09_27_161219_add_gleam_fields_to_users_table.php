<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nama')->after('id');
            $table->string('username')->unique()->after('email');
            $table->string('nomor_telepon')->after('username');
            $table->enum('role', ['admin', 'manajemen', 'nakes', 'user'])->default('user')->after('nomor_telepon');
            $table->string('diagnosa_medis')->nullable()->after('role');
            $table->dropColumn('name'); // Hapus kolom name bawaan Laravel
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->dropColumn(['nama', 'username', 'nomor_telepon', 'role', 'diagnosa_medis']);
        });
    }
};

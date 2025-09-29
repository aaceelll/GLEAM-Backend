<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tempat_lahir')) {
                $table->string('tempat_lahir')->nullable();
            }
            if (!Schema::hasColumn('users', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable();
            }
            if (!Schema::hasColumn('users', 'jenis_kelamin')) {
                $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tempat_lahir', 'tanggal_lahir', 'jenis_kelamin']);
        });
    }
};
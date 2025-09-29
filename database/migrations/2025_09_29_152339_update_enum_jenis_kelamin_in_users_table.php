<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Ubah enum agar sesuai dengan UI
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Kembalikan ke enum sebelumnya jika dibatalkan
            $table->enum('jenis_kelamin', ['male', 'female'])->nullable()->change();
        });
    }
};

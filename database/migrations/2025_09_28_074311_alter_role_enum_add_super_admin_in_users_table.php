<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sesuaikan urutan/daftar enum sesuai kebutuhan kamu
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM('super_admin','admin','manajemen','nakes','user')
            NOT NULL DEFAULT 'user'
        ");
    }

    public function down(): void
    {
        // Kembalikan ke enum sebelumnya (tanpa super_admin). Sesuaikan dengan kondisi semula.
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM('admin','manajemen','nakes','user')
            NOT NULL DEFAULT 'user'
        ");
    }
};

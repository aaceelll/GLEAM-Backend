<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cek dan tambahkan kelurahan
            if (!Schema::hasColumn('users', 'kelurahan')) {
                $table->string('kelurahan')->nullable()->after('alamat');
            }
            
            // Cek dan tambahkan rw
            if (!Schema::hasColumn('users', 'rw')) {
                $table->string('rw', 10)->nullable()->after('kelurahan');
            }
            
            // Cek dan tambahkan latitude
            if (!Schema::hasColumn('users', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('rw');
            }
            
            // Cek dan tambahkan longitude
            if (!Schema::hasColumn('users', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            
            // Cek dan tambahkan address
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['kelurahan', 'rw', 'latitude', 'longitude', 'address'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
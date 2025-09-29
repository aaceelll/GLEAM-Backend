<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // MedicalDiagnosisSeeder::class, // seeder lama kamu (biarkan)
            // AdminUserSeeder::class,        // seeder lama kamu (biarkan)
            MateriSeeder::class,           // ⬅️ tambahkan ini agar "Diabetes Melitus" ada
        ]);
    }
}

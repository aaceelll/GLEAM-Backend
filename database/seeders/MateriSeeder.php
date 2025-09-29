<?php

namespace Database\Seeders;

use App\Models\Materi;
use Illuminate\Database\Seeder;

class MateriSeeder extends Seeder
{
    public function run(): void
    {
        Materi::updateOrCreate(
            ['slug' => 'diabetes-melitus'],
            ['nama' => 'Diabetes Melitus', 'deskripsi' => 'Materi khusus Diabetes Melitus']
        );
    }
}

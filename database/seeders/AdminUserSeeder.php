<!-- <?php

// namespace Database\Seeders;

// use Illuminate\Database\Seeder;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Hash;

// class AdminUserSeeder extends Seeder
// {
//     public function run()
//     {
//         $users = [
//             [
//                 'nama' => 'Administrator GLEAM',
//                 'email' => 'admin@gleam.com',
//                 'username' => 'admin',
//                 'nomor_telepon' => '081234567890',
//                 'role' => 'admin',
//                 'password' => Hash::make('admin123'),
//                 'email_verified_at' => now(),
//             ],
//             [
//                 'nama' => 'Dr. Sarah Wijaya',
//                 'email' => 'nakes@gleam.com', 
//                 'username' => 'dr.sarah',
//                 'nomor_telepon' => '081234567891',
//                 'role' => 'nakes',
//                 'password' => Hash::make('nakes123'),
//                 'email_verified_at' => now(),
//             ],
//             [
//                 'nama' => 'Manager Kesehatan',
//                 'email' => 'manager@gleam.com',
//                 'username' => 'manager',
//                 'nomor_telepon' => '081234567892', 
//                 'role' => 'manajemen',
//                 'password' => Hash::make('manager123'),
//                 'email_verified_at' => now(),
//             ],
//             [
//                 'nama' => 'John Doe',
//                 'email' => 'user@gleam.com',
//                 'username' => 'johndoe',
//                 'nomor_telepon' => '081234567893',
//                 'role' => 'user',
//                 'diagnosa_medis' => 'Diabetes Mellitus Tipe 2',
//                 'password' => Hash::make('user123'),
//                 'email_verified_at' => now(),
//             ],
//         ];

//         foreach ($users as $user) {
//             DB::table('users')->insert([
//                 'nama' => $user['nama'],
//                 'email' => $user['email'],
//                 'username' => $user['username'],
//                 'nomor_telepon' => $user['nomor_telepon'],
//                 'role' => $user['role'],
//                 'diagnosa_medis' => $user['diagnosa_medis'] ?? null,
//                 'password' => $user['password'],
//                 'email_verified_at' => $user['email_verified_at'],
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ]);
//         }
//     }
// } -->

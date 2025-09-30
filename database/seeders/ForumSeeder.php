<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ForumCategory;
use App\Models\ForumThread;
use App\Models\ForumReply;
use App\Models\User;
use Carbon\Carbon;

class ForumSeeder extends Seeder
{
    public function run(): void
    {
        // ===== KATEGORI FORUM =====
        $categories = [
            [
                'name' => 'Tips & Trik',
                'slug' => 'tips-trik',
                'icon' => '💡',
                'color' => '#10b981',
                'description' => 'Berbagi tips sehari-hari mengelola diabetes',
                'thread_count' => 0
            ],
            [
                'name' => 'Cerita Inspiratif',
                'slug' => 'cerita-inspiratif',
                'icon' => '✨',
                'color' => '#f59e0b',
                'description' => 'Kisah sukses dan motivasi dari sesama pejuang diabetes',
                'thread_count' => 0
            ],
            [
                'name' => 'Resep & Makanan',
                'slug' => 'resep-makanan',
                'icon' => '🍽️',
                'color' => '#ef4444',
                'description' => 'Resep sehat dan lezat untuk penderita diabetes',
                'thread_count' => 0
            ],
            [
                'name' => 'Olahraga & Aktivitas',
                'slug' => 'olahraga',
                'icon' => '🏃',
                'color' => '#3b82f6',
                'description' => 'Tips olahraga dan aktivitas fisik yang aman',
                'thread_count' => 0
            ],
            [
                'name' => 'Pengobatan & Terapi',
                'slug' => 'pengobatan',
                'icon' => '💊',
                'color' => '#8b5cf6',
                'description' => 'Diskusi seputar obat, insulin, dan terapi',
                'thread_count' => 0
            ],
            [
                'name' => 'Dukungan Emosional',
                'slug' => 'dukungan-emosional',
                'icon' => '❤️',
                'color' => '#ec4899',
                'description' => 'Saling mendukung dan berbagi perasaan',
                'thread_count' => 0
            ],
        ];

        // Insert atau update kategori
        foreach ($categories as $category) {
            ForumCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        // ===== CONTOH THREAD PUBLIC =====
        $user = User::where('role', 'user')->first();
        
        if ($user) {
            $publicThreads = [
                [
                    'user_id' => $user->id,
                    'category_id' => 1,
                    'title' => 'Tips Mengelola Gula Darah Saat Puasa',
                    'content' => 'Halo teman-teman! Mau share pengalaman saya mengelola gula darah saat puasa. Yang pertama, pastikan sahur dengan makanan tinggi serat seperti oatmeal. Kedua, jangan lupa cek gula darah secara rutin. Bagaimana pengalaman kalian?',
                    'is_private' => false,
                    'is_pinned' => true,
                    'view_count' => 156,
                    'reply_count' => 0,
                    'like_count' => 28,
                    'last_activity_at' => Carbon::now()->subHours(2),
                ],
                [
                    'user_id' => $user->id,
                    'category_id' => 3,
                    'title' => 'Resep Nasi Shirataki yang Enak!',
                    'content' => 'Buat yang suka nasi tapi harus diet, coba resep nasi shirataki ini. Rendah kalori dan rendah karbohidrat. Cara masaknya: 1) Bilas shirataki sampai bersih, 2) Rebus 2-3 menit, 3) Tiriskan dan tumis dengan sedikit minyak. Enak banget!',
                    'is_private' => false,
                    'view_count' => 89,
                    'reply_count' => 0,
                    'like_count' => 15,
                    'last_activity_at' => Carbon::now()->subHours(5),
                ],
                [
                    'user_id' => $user->id,
                    'category_id' => 2,
                    'title' => 'Perjalanan Saya Melawan Diabetes Tipe 2',
                    'content' => 'Sudah 3 tahun saya didiagnosis diabetes tipe 2. Awalnya shock dan takut. Tapi sekarang saya sudah bisa mengontrol dengan baik. HbA1c saya turun dari 9.5% jadi 6.2%! Kuncinya adalah disiplin dan konsisten. Semangat untuk semua!',
                    'is_private' => false,
                    'view_count' => 234,
                    'reply_count' => 0,
                    'like_count' => 67,
                    'last_activity_at' => Carbon::now()->subDay(),
                ],
                [
                    'user_id' => $user->id,
                    'category_id' => 4,
                    'title' => 'Olahraga Ringan untuk Pemula',
                    'content' => 'Halo! Saya baru mulai rutin olahraga nih. Sekarang saya jalan kaki 30 menit setiap pagi. Gula darah saya jadi lebih stabil. Ada yang punya tips olahraga ringan lainnya?',
                    'is_private' => false,
                    'view_count' => 67,
                    'reply_count' => 0,
                    'like_count' => 12,
                    'last_activity_at' => Carbon::now()->subHours(8),
                ],
                [
                    'user_id' => $user->id,
                    'category_id' => 5,
                    'title' => 'Pengalaman Pakai Insulin Pertama Kali',
                    'content' => 'Akhirnya dokter meresepkan insulin untuk saya. Awalnya takut nyuntik sendiri, tapi ternyata ga sesakit yang dibayangkan. Ada tips buat yang baru mulai pakai insulin?',
                    'is_private' => false,
                    'view_count' => 45,
                    'reply_count' => 0,
                    'like_count' => 8,
                    'last_activity_at' => Carbon::now()->subHours(12),
                ],
            ];

            foreach ($publicThreads as $threadData) {
                // Check if thread already exists
                $existingThread = ForumThread::where('user_id', $threadData['user_id'])
                    ->where('title', $threadData['title'])
                    ->first();

                if (!$existingThread) {
                    $thread = ForumThread::create($threadData);
                    
                    // Update category count
                    $category = ForumCategory::find($threadData['category_id']);
                    if ($category) {
                        $category->increment('thread_count');
                    }
                }
            }

            // ===== CONTOH THREAD PRIVATE =====
            $privateThreads = [
                [
                    'user_id' => $user->id,
                    'category_id' => 5,
                    'title' => 'Gula Darah Saya Sering Naik Turun',
                    'content' => 'Dok, gula darah saya akhir-akhir ini tidak stabil. Pagi bisa 120, siang tiba-tiba 200. Padahal sudah minum obat teratur. Apa yang harus saya lakukan?',
                    'is_private' => true,
                    'view_count' => 5,
                    'reply_count' => 0,
                    'like_count' => 0,
                    'last_activity_at' => Carbon::now()->subHours(3),
                ],
                [
                    'user_id' => $user->id,
                    'category_id' => 5,
                    'title' => 'Bolehkah Penderita Diabetes Makan Buah?',
                    'content' => 'Saya sering mendengar buah mengandung gula yang tinggi. Apakah aman untuk penderita diabetes? Buah apa yang boleh dan tidak boleh dikonsumsi?',
                    'is_private' => true,
                    'view_count' => 3,
                    'reply_count' => 0,
                    'like_count' => 0,
                    'last_activity_at' => Carbon::now()->subHours(6),
                ],
            ];

            foreach ($privateThreads as $threadData) {
                // Check if thread already exists
                $existingThread = ForumThread::where('user_id', $threadData['user_id'])
                    ->where('title', $threadData['title'])
                    ->first();

                if (!$existingThread) {
                    ForumThread::create($threadData);
                }
            }
        }

        $this->command->info('✅ Forum categories dan threads berhasil di-seed!');
    }
}
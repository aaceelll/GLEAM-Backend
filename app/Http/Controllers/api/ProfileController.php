<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class ProfileController extends Controller
{
    // GET /api/profile
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => $request->user(),
        ]);
    }

    // PUT /api/profile
    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        // Validate tanggal_lahir terlebih dahulu untuk memastikan formatnya benar
        $request->validate([
            'tanggal_lahir' => 'nullable|date_format:Y-m-d',
        ], [
            'tanggal_lahir.date_format' => 'Format tanggal lahir harus YYYY-MM-DD (contoh: 2007-05-20)',
        ]);

        $data = $request->validate([
            // ---- Field akun umum (sometimes = optional, hanya validate kalau dikirim)
            'nama'          => ['sometimes','required','string','max:255'],
            'umur'          => ['nullable', 'integer','min:10','max:120'],
            'nomor_telepon' => ['nullable','string','max:30'],
            'alamat'        => ['nullable','string','max:255'],

            // ---- Field profil kesehatan
            'tempat_lahir'                => 'nullable|string|max:255',
            'tanggal_lahir'               => 'nullable|date_format:Y-m-d',
            'jenis_kelamin'               => 'nullable|in:Laki-laki,Perempuan',
            'pekerjaan'                   => 'nullable|string|max:255',
            'pendidikan_terakhir'         => 'nullable|string|max:255',
            'riwayat_kesehatan'           => 'nullable|string|max:255',
            'riwayat_pelayanan_kesehatan' => 'nullable|string|max:255',
            'riwayat_merokok'             => 'nullable|in:Perokok Aktif,Mantan Perokok,Tidak Pernah Merokok,Tidak Ada Informasi',
            'berat_badan'                 => 'nullable|numeric|min:0',
            'tinggi_badan'                => 'nullable|numeric|min:0',
            'indeks_bmi'                  => 'nullable|numeric|min:0',
            'riwayat_penyakit_jantung'    => 'nullable|in:Ya,Tidak',
            'durasi_diagnosis'            => 'nullable|string|max:255',
            'lama_terdiagnosis'           => 'nullable|string|max:255',
            'berobat_ke_dokter'           => 'nullable|in:Sudah,Belum',
            'sudah_berobat'               => 'nullable|in:Sudah,Belum Pernah',

            // ---- Field lokasi
            'kelurahan'                   => 'nullable|in:Pedalangan,Padangsari',
            'rw'                          => 'nullable|string|max:10',
            'latitude'                    => 'nullable|numeric',
            'longitude'                   => 'nullable|numeric',
            'address'                     => 'nullable|string',
        ], [
            'umur.min' => 'Umur minimal harus 10 tahun.',
            'umur.max' => 'Umur maksimal adalah 120 tahun.',
            'tanggal_lahir.date_format' => 'Format tanggal lahir harus YYYY-MM-DD (contoh: 2007-05-20)',
        ]);

        // Validasi tambahan untuk tanggal lahir dan umur
        if (isset($data['tanggal_lahir'])) {
            try {
                // Parse tanggal lahir dengan format eksplisit
                $birthDate = Carbon::createFromFormat('Y-m-d', $data['tanggal_lahir'])->startOfDay();
                $now = Carbon::now()->startOfDay();
                
                // Pastikan tanggal lahir tidak di masa depan
                if ($birthDate->gt($now)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tanggal lahir tidak boleh di masa depan.',
                    ], 422);
                }
                
                // Hitung umur dalam tahun penuh
                $calculatedAge = $birthDate->diffInYears($now);
                
                // Validasi umur minimal 10 tahun
                if ($calculatedAge < 10) {
                    return response()->json([
                        'success' => false,
                        'message' => "Umur minimal harus 10 tahun. Berdasarkan tanggal lahir {$data['tanggal_lahir']}, umur Anda adalah {$calculatedAge} tahun.",
                    ], 422);
                }
                
                // Validasi umur maksimal 120 tahun
                if ($calculatedAge > 120) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tanggal lahir tidak valid. Umur maksimal adalah 120 tahun.',
                    ], 422);
                }
                
                // Auto-update umur berdasarkan perhitungan
                $data['umur'] = $calculatedAge;
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format tanggal lahir tidak valid. Gunakan format YYYY-MM-DD (contoh: 2007-05-20)',
                ], 422);
            }
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'data'    => $user->fresh(),
        ]);
    }
}
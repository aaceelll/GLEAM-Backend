<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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

        // Validasi ringan tanggal_lahir agar error message rapi
        $request->validate([
            'tanggal_lahir' => 'nullable|date_format:Y-m-d',
        ], [
            'tanggal_lahir.date_format' => 'Format tanggal lahir harus YYYY-MM-DD (contoh: 2007-05-20)',
        ]);

        // ✅ Tambahkan email & username, unique tapi mengabaikan user saat ini
        $data = $request->validate([
            // ---- Akun umum
            'nama'           => ['sometimes','required','string','max:255'],
            'email'          => ['sometimes','required','email', Rule::unique('users', 'email')->ignore($user->id)],
            'username'       => ['sometimes','required','string','max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'nomor_telepon'  => ['nullable','string','max:30'],
            'alamat'         => ['nullable','string','max:255'],
            'umur'           => ['nullable','integer','min:10','max:120'],

            // ---- Profil kesehatan
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

            // ---- Lokasi
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

        // Hitung & validasi umur berdasarkan tanggal_lahir (jika dikirim)
        if (isset($data['tanggal_lahir'])) {
            try {
                $birthDate = Carbon::createFromFormat('Y-m-d', $data['tanggal_lahir'])->startOfDay();
                $now = Carbon::now()->startOfDay();

                if ($birthDate->gt($now)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tanggal lahir tidak boleh di masa depan.',
                    ], 422);
                }

                $calculatedAge = $birthDate->diffInYears($now);

                if ($calculatedAge < 10) {
                    return response()->json([
                        'success' => false,
                        'message' => "Umur minimal harus 10 tahun. Berdasarkan tanggal lahir {$data['tanggal_lahir']}, umur Anda adalah {$calculatedAge} tahun.",
                    ], 422);
                }

                if ($calculatedAge > 120) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tanggal lahir tidak valid. Umur maksimal adalah 120 tahun.',
                    ], 422);
                }

                // auto set umur
                $data['umur'] = $calculatedAge;
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format tanggal lahir tidak valid. Gunakan format YYYY-MM-DD (contoh: 2007-05-20)',
                ], 422);
            }
        }

        // Simpan
        $user->fill($data);
        $user->save();

        return response()->json([
            'success' => true,
            'data'    => $user->fresh(),
        ]);
    }

    // PATCH /api/profile/password
    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'old_password'              => ['required','string'],
            'new_password'              => ['required','string','min:8'],
            'new_password_confirmation' => ['required','same:new_password'],
        ]);

        if (!Hash::check($validated['old_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.',
            ], 422);
        }

        $user->password = $validated['new_password'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    // PUT /api/profile/personal-info  (opsional, sesuai route-mu)
    public function updatePersonalInfo(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'nomor_telepon' => ['nullable','string','max:30'],
            'alamat'        => ['nullable','string','max:255'],
        ]);

        $user->fill($data)->save();

        return response()->json([
            'success' => true,
            'data'    => $user->fresh(),
        ]);
    }
}

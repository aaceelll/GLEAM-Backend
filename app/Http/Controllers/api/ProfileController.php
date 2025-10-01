<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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

        $data = $request->validate([
            // ---- Field akun umum
            'nama'          => ['required','string','max:255'],
            'email'         => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'username'      => ['required','string','max:255', Rule::unique('users','username')->ignore($user->id)],
            'nomor_telepon' => ['nullable','string','max:30'],
            'alamat'        => ['nullable','string','max:255'],

            // ---- Field profil kesehatan
            'tempat_lahir'                => 'nullable|string|max:255',
            'tanggal_lahir'               => 'nullable|date',
            'umur'                        => 'nullable|integer|min:0',
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
        ]);

        // Auto-calc BMI jika ada berat & tinggi
        if (isset($data['berat_badan'], $data['tinggi_badan']) && (float)$data['tinggi_badan'] > 0) {
            $m = $data['tinggi_badan'] / 100;
            $data['indeks_bmi'] = round($data['berat_badan'] / ($m * $m), 2);
        }

        // Tandai user sudah melengkapi profil
        $data['has_completed_profile'] = true;

        $user->fill($data)->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user'    => $user->fresh(), // Intelephense aman karena sudah kita type-hint
        ]);
    }

    // PATCH /api/profile/password
    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'old_password'              => ['required'],
            'new_password'              => ['required','min:8','confirmed'], // kirim: new_password & new_password_confirmation
        ]);

        if (! Hash::check($request->old_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password lama salah.'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['success' => true, 'message' => 'Password berhasil diperbarui.']);
    }
}
        
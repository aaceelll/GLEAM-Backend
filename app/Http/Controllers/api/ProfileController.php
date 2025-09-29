<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    // ✅ Get profile info
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    // ✅ Update personal information
    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'nama' => 'nullable|string|max:255',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'umur' => 'nullable|integer|min:0',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'pekerjaan' => 'nullable|string|max:255',
            'pendidikan_terakhir' => 'nullable|string|max:255',
            'riwayat_kesehatan' => 'nullable|string|max:255',
            'riwayat_pelayanan_kesehatan' => 'nullable|string|max:255',
            'riwayat_merokok' => 'nullable|in:Perokok Aktif,Mantan Perokok,Tidak Pernah Merokok,Tidak Ada Informasi',
            'berat_badan' => 'nullable|numeric|min:0',
            'tinggi_badan' => 'nullable|numeric|min:0',
            'indeks_bmi' => 'nullable|numeric|min:0',
            'riwayat_penyakit_jantung' => 'nullable|in:Ya,Tidak',
            'durasi_diagnosis' => 'nullable|string|max:255',
            'lama_terdiagnosis' => 'nullable|string|max:255',
            'berobat_ke_dokter' => 'nullable|in:Sudah,Belum',
            'sudah_berobat' => 'nullable|in:Sudah,Belum Pernah',
        ]);

        // ✅ Auto-calculate BMI jika ada berat dan tinggi
        if (isset($data['berat_badan']) && isset($data['tinggi_badan']) && $data['tinggi_badan'] > 0) {
            $tinggiMeter = $data['tinggi_badan'] / 100;
            $data['indeks_bmi'] = round($data['berat_badan'] / ($tinggiMeter * $tinggiMeter), 2);
        }

        // ✅ Tandai user sudah melengkapi profil
        $data['has_completed_profile'] = true;

        // ✅ Update data user
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }
}

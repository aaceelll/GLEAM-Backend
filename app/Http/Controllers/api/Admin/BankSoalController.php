<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Mengelola data bank soal pada sistem edukasi, crud, relasi ke materi
class BankSoalController extends Controller
{
    // GET /api/admin/bank-soal ( ambil daftar seluruh bank soal yang tersedia)
    public function index()
    {
        $banks = BankSoal::withCount('soal')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($b) {
                $total = (int) $b->soal_count;
                return [
                    'id'        => $b->id,
                    'nama'      => $b->nama,
                    'status'    => $b->status,
                    'totalSoal' => $total,
                    'createdAt'  => optional($b->created_at)->toDateTimeString(),
                    'updatedAt' => optional($b->updated_at)->toDateTimeString(),
                ];
            });

        return response()->json(['data' => $banks]);
    }

    // POST /api/admin/bank-soal (buat soal baru)
    // Sistem deteksi tipe otomatis (pre/post) berdasarkan nama bank soal dan menghubungkannya
    public function store(Request $request)
    {
        // Validasi input
        $data = $request->validate([
            'nama' => 'required|string|max:255|unique:question_banks,nama',
        ],[
            'nama.required' => 'Nama bank soal wajib diisi.',
            'nama.unique'   => 'Nama bank soal sudah digunakan.',
        ]);

        $data['status'] = 'draft'; // default, dan nanti otomatis berubah dari Soal::syncBankVisibility
        $bank = BankSoal::create($data);

        // Auto-link ke materi "diabetes-melitus"
        $materi = Materi::where('slug', 'diabetes-melitus')->first();

        if ($materi) {
            // Deteksi tipe pre/post dari nama bank soal
            $bankNameLower = strtolower($bank->nama);
            $detectedTipe = str_contains($bankNameLower, 'post') ? 'post' : 'pre';

            // Cek apakah sudah ada link dengan tipe yang sama
            $exists = DB::table('materi_bank_soal')
                ->where('materi_id', $materi->id)
                ->where('bank_id', $bank->id)
                ->where('tipe', $detectedTipe)
                ->exists();

            // Jika belum ada, buat relasi baru
            if (!$exists) {
                DB::table('materi_bank_soal')->insert([
                    'materi_id' => $materi->id,
                    'bank_id'   => $bank->id,
                    'tipe'      => $detectedTipe, 
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json($bank, 201);
    }

    // PATCH /api/admin/bank-soal/{id} (update bank soal) 
    public function update($id, Request $request)
    {
        $bank = BankSoal::findOrFail($id);

        $bank->update($request->validate([
            'nama'   => 'sometimes|string|max:255',
        ]));

        return response()->json(['ok' => true, 'data' => $bank]);
    }

    // DELETE /api/admin/bank-soal/{id} (hapus bank soal)
    public function destroy($id)
    {
        $bank = BankSoal::findOrFail($id);

        // Hapus relasi ke tabel pivot materi_bank_soal
        DB::table('materi_bank_soal')->where('bank_id', $id)->delete();

        // Hapus bank soal
        $bank->delete();

        return response()->json(['ok' => true]);
    }

    // menghubungkan bank soal tertentu dengan materi tertentu 
    public function linkToMateri(Request $request, $bankId)
    {
        $validated = $request->validate([
            'materi_id' => 'required|exists:materi,id',
            'tipe'      => 'required|in:pre,post',
        ]);

        // Cek apakah sudah ada link serupa
        $exists = DB::table('materi_bank_soal')
            ->where('materi_id', $validated['materi_id'])
            ->where('bank_id', $bankId)
            ->where('tipe', $validated['tipe'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Bank soal sudah terhubung ke materi dengan tipe yang sama',
            ], 422);
        }

        // Tambahkan relasi baru
        DB::table('materi_bank_soal')->insert([
            'materi_id'  => $validated['materi_id'],
            'bank_id'    => $bankId,
            'tipe'       => $validated['tipe'],
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Bank soal berhasil dihubungkan ke materi',
            'data' => [
                'materi_id' => $validated['materi_id'],
                'bank_id'   => $bankId,
                'tipe'      => $validated['tipe'],
            ],
        ]);
    }

    // menampilkan daftar materi yang terhubung ke bank soal tertentu
    public function getMateriLinks($bankId)
    {
        $links = DB::table('materi_bank_soal as mbs')
            ->join('materi as m', 'm.id', '=', 'mbs.materi_id')
            ->where('mbs.bank_id', $bankId)
            ->select(
                'm.id',
                'm.nama',
                'm.slug',
                'mbs.tipe',
                'mbs.is_active'
            )
            ->get();

        return response()->json($links);
    }

    // Menghapus hubungan antara bank soal dan materi tertentu
    public function unlinkFromMateri($bankId, $materiId)
    {
        DB::table('materi_bank_soal')
            ->where('bank_id', $bankId)
            ->where('materi_id', $materiId)
            ->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Link berhasil dihapus',
        ]);
    }
}

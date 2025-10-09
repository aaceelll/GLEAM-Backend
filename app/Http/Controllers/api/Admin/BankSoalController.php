<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Materi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankSoalController extends Controller
{
    /**
     * GET /api/admin/bank-soal
     * List semua bank soal dengan jumlah soal
     */
    public function index()
    {
        $banks = BankSoal::withCount('soal')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'nama' => $b->nama,
                'status' => $b->status,
                'totalSoal' => $b->soal_count,
                'updatedAt' => $b->updated_at,
            ]);

        return response()->json($banks);
    }

    /**
     * POST /api/admin/bank-soal
     * Buat bank soal baru
     * ✅ AUTO-DETECT TIPE (PRE/POST) DARI NAMA
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'status' => 'nullable|in:draft,publish',
        ]);
        
        $data['status'] = $data['status'] ?? 'draft';
        $bank = BankSoal::create($data);

        // ✅ AUTO-LINK KE MATERI DIABETES MELITUS
        $materi = Materi::where('slug', 'diabetes-melitus')->first();
        
        if ($materi) {
            // ✅ DETECT TIPE DARI NAMA BANK SOAL
            $bankNameLower = strtolower($bank->nama);
            $detectedTipe = str_contains($bankNameLower, 'post') ? 'post' : 'pre';
            
            // Cek apakah sudah ada link dengan tipe yang sama
            $exists = DB::table('materi_bank_soal')
                ->where('materi_id', $materi->id)
                ->where('bank_id', $bank->id)
                ->where('tipe', $detectedTipe)
                ->exists();
            
            if (!$exists) {
                DB::table('materi_bank_soal')->insert([
                    'materi_id' => $materi->id,
                    'bank_id' => $bank->id,
                    'tipe' => $detectedTipe, // ✅ OTOMATIS DETECT!
                    'is_active' => true,
                    'urutan' => DB::table('materi_bank_soal')
                        ->where('materi_id', $materi->id)
                        ->max('urutan') + 1 ?? 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json($bank, 201);
    }

    /**
     * PATCH /api/admin/bank-soal/{id}
     * Update bank soal
     */
    public function update($id, Request $request)
    {
        $bank = BankSoal::findOrFail($id);
        
        $bank->update($request->validate([
            'nama' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:draft,publish',
        ]));
        
        return response()->json(['ok' => true, 'data' => $bank]);
    }

    /**
     * DELETE /api/admin/bank-soal/{id}
     * Hapus bank soal beserta semua soalnya
     */
    public function destroy($id)
    {
        $bank = BankSoal::findOrFail($id);
        
        // Hapus link ke materi jika ada
        DB::table('materi_bank_soal')->where('bank_id', $id)->delete();
        
        // Hapus bank dan soal-soalnya (cascade via model relationship)
        $bank->delete();
        
        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/admin/bank-soal/{bankId}/link-materi
     * Hubungkan bank soal ke materi
     */
    public function linkToMateri(Request $request, $bankId)
    {
        $validated = $request->validate([
            'materi_id' => 'required|exists:materi,id',
            'tipe' => 'required|in:pre,post',
            'urutan' => 'nullable|integer',
        ]);

        // Cek apakah sudah ada link yang sama
        $exists = DB::table('materi_bank_soal')
            ->where('materi_id', $validated['materi_id'])
            ->where('bank_id', $bankId)
            ->where('tipe', $validated['tipe'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Bank soal sudah terhubung ke materi dengan tipe yang sama'
            ], 422);
        }

        // Insert link
        DB::table('materi_bank_soal')->insert([
            'materi_id' => $validated['materi_id'],
            'bank_id' => $bankId,
            'tipe' => $validated['tipe'],
            'urutan' => $validated['urutan'] ?? 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Bank soal berhasil dihubungkan ke materi',
            'data' => [
                'materi_id' => $validated['materi_id'],
                'bank_id' => $bankId,
                'tipe' => $validated['tipe'],
            ]
        ]);
    }

    /**
     * GET /api/admin/bank-soal/{bankId}/materi-links
     * Lihat materi mana saja yang terhubung ke bank soal ini
     */
    public function getMateriLinks($bankId)
    {
        $links = DB::table('materi_bank_soal as mbs')
            ->join('materi as m', 'm.id', '=', 'mbs.materi_id')
            ->where('mbs.bank_id', $bankId)
            ->select('m.id', 'm.nama', 'm.slug', 'mbs.tipe', 'mbs.urutan', 'mbs.is_active')
            ->get();

        return response()->json($links);
    }

    /**
     * DELETE /api/admin/bank-soal/{bankId}/unlink-materi/{materiId}
     * Putuskan hubungan bank soal dengan materi
     */
    public function unlinkFromMateri($bankId, $materiId)
    {
        DB::table('materi_bank_soal')
            ->where('bank_id', $bankId)
            ->where('materi_id', $materiId)
            ->delete();

        return response()->json(['ok' => true, 'message' => 'Link berhasil dihapus']);
    }
}

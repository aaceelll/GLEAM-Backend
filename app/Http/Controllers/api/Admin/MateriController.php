<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\TestModel;
use App\Models\KontenMateri;
use App\Models\BankSoal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MateriController extends Controller
{
    /**
     * ===== MASTER MATERI (ADMIN OPSIONAL) =====
     * GET /api/admin/materi?slug=diabetes-melitus
     */
    public function index(Request $r): JsonResponse
    {
        $q = Materi::query()->orderBy('nama');
        if ($slug = $r->query('slug')) {
            $q->where('slug', $slug);
        }
        return response()->json($q->get());
    }

    /**
     * ===== USER: LIST KONTEN + TES/BANK (AUTO) =====
     * GET /api/materi/konten?slug=diabetes-melitus
     *
     * - Prioritas: ambil TES publish (tabel tests)
     * - Jika kosong: fallback ke BANK SOAL yang terhubung materi (pivot materi_bank_soal),
     *                tampilkan sebagai "tes" juga agar FE tidak berubah.
     */
    public function listKontenPublic(Request $request): JsonResponse
    {
        $slug = $request->query('slug', 'diabetes-melitus');

        $materi = Materi::where('slug', $slug)->first();
        if (!$materi) {
            return response()->json(['konten' => [], 'tes' => []], 200);
        }

        // Konten edukasi
        $konten = KontenMateri::where('materi_id', $materi->id)
            ->orderBy('created_at', 'desc')
            ->get(['id','judul','video_id','file_url','deskripsi','created_at','updated_at']);

        // 1) Coba ambil dari TESTS (mode lama)
        $tests = TestModel::where('materi_id', $materi->id)
            ->where('status', 'publish')
            ->with(['bank.soal' => function ($q) {
                $q->select('id','bank_id','teks','tipe','opsi');
            }])
            ->get(['id','nama','tipe','status','bank_id']);

        $banks = DB::table('materi_bank_soal as mbs')
    ->join('question_banks as qb', 'qb.id', '=', 'mbs.bank_id')
    ->leftJoin('questions as q', 'q.bank_id', '=', 'qb.id')
    ->where('mbs.materi_id', $materi->id)
    ->groupBy('qb.id', 'qb.nama')
    ->select('qb.id as bank_id', 'qb.nama', DB::raw('COUNT(q.id) as total_soal'))
    ->get();

$tesFormatted = $banks->filter(fn($b) => (int)$b->total_soal > 0) // <-- BARIS INI YG MEMFILTER
    ->map(function ($b) {
        return [
            'id'        => (int)$b->bank_id,
            'nama'      => $b->nama,
            'totalSoal' => (int)$b->total_soal,
            'bank_id'   => (int)$b->bank_id,
            'source'    => 'banks',
        ];
    })->values();

        // 2) Fallback: ambil BANK SOAL yang terhubung ke materi via pivot `materi_bank_soal`
        // Hitung jumlah soal per bank, hanya tampilkan yang punya >=1 soal
        $banks = DB::table('materi_bank_soal as mbs')
            ->join('question_banks as qb', 'qb.id', '=', 'mbs.bank_id')
            ->leftJoin('questions as q', 'q.bank_id', '=', 'qb.id')
            ->where('mbs.materi_id', $materi->id)
            ->groupBy('qb.id', 'qb.nama')
            ->select('qb.id as bank_id', 'qb.nama', DB::raw('COUNT(q.id) as total_soal'))
            ->get();

        $tesFormatted = $banks->filter(fn($b) => (int)$b->total_soal > 0)->map(function ($b) {
            // Id kita set = bank_id saja. Frontend nanti panggil /api/materi/tes-by-bank/{bank_id}
            return [
                'id'          => (int)$b->bank_id,      // gunakan sebagai identifier
                'nama'        => $b->nama,
                'deskripsi'   => null,
                'totalSoal'   => (int)$b->total_soal,
                'durasiMenit' => null,
                'bank_id'     => (int)$b->bank_id,
                'source'      => 'banks',               // penanda "mode fallback"
            ];
        })->values();

        return response()->json(['konten' => $konten, 'tes' => $tesFormatted], 200);
    }

    /**
     * ===== USER: DETAIL TES (MODE TESTS) =====
     * GET /api/materi/tes/{id}
     */
    public function showTesPublic($id): JsonResponse
    {
        $tes = TestModel::with([
            'bank.soal' => function ($q) {
                $q->select('id','bank_id','teks','tipe','opsi','bobot','kunci');
            }
        ])->where('status', 'publish')->findOrFail($id);

        return response()->json([
            'id'        => $tes->id,
            'nama'      => $tes->nama,
            'tipe'      => $tes->tipe,
            'materi_id' => $tes->materi_id,
            'totalSoal' => $tes->bank?->soal->count() ?? 0,
            'soal'      => $tes->bank?->soal ?? [],
            'source'    => 'tests',
        ]);
    }

    /**
     * ===== USER: DETAIL TES (MODE BANKS/FALLBACK) =====
     * GET /api/materi/tes-by-bank/{bankId}
     */
    public function showTesByBank($bankId): JsonResponse
    {
        $bank = BankSoal::with(['soal' => function ($q) {
            $q->select('id','bank_id','teks','tipe','opsi','bobot','kunci');
        }])->findOrFail($bankId);

        return response()->json([
            'bank_id'   => $bank->id,
            'nama'      => $bank->nama,
            'totalSoal' => $bank->soal->count(),
            'soal'      => $bank->soal,
            'source'    => 'banks',
        ], 200);
    }

    /**
     * ===== ADMIN: LIST KONTEN =====
     * GET /api/admin/materi/konten?slug=diabetes-melitus
     */
    public function listKonten(Request $request): JsonResponse
    {
        $slug = $request->query('slug', 'diabetes-melitus');

        $materi = Materi::where('slug', $slug)->first();
        if (!$materi) return response()->json(['data' => []], 200);

        $konten = KontenMateri::where('materi_id', $materi->id)
            ->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $konten], 200);
    }

    /**
     * ===== ADMIN: TAMBAH KONTEN =====
     * POST /api/admin/materi/konten
     */
    public function storeKonten(Request $request): JsonResponse
    {
        $request->validate([
            'judul'     => 'required|string|max:255',
            'video_id'  => 'nullable|string',
            'file_pdf'  => 'required|file|mimes:pdf|max:10240',
            'deskripsi' => 'required|string',
        ]);

        $materi = Materi::where('slug', 'diabetes-melitus')->first();
        if (!$materi) return response()->json(['message' => 'Materi tidak ditemukan'], 404);

        $path = $request->file('file_pdf')->store('materi', 'public');
        $url  = asset(Storage::url($path));

        $konten = KontenMateri::create([
            'materi_id' => $materi->id,
            'judul'     => $request->judul,
            'video_id'  => $request->video_id,
            'file_url'  => $url,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json(['message' => 'Konten berhasil ditambahkan','data' => $konten], 201);
    }

    /**
     * ===== ADMIN: UPDATE KONTEN =====
     * PATCH /api/admin/materi/konten/{id}
     */
    public function updateKonten(Request $request, $id): JsonResponse
    {
        $request->validate([
            'judul'     => 'required|string|max:255',
            'video_id'  => 'nullable|string',
            'file_pdf'  => 'nullable|file|mimes:pdf|max:10240',
            'deskripsi' => 'required|string',
        ]);

        $konten = KontenMateri::findOrFail($id);

        if ($request->hasFile('file_pdf')) {
            $this->deleteFileByPublicUrl($konten->file_url);
            $newPath = $request->file('file_pdf')->store('materi', 'public');
            $konten->file_url = asset(Storage::url($newPath));
        }

        $konten->judul     = $request->judul;
        $konten->video_id  = $request->video_id;
        $konten->deskripsi = $request->deskripsi;
        $konten->save();

        return response()->json(['message' => 'Konten berhasil diperbarui','data' => $konten], 200);
    }

    /**
     * ===== ADMIN: HAPUS KONTEN =====
     * DELETE /api/admin/materi/konten/{id}
     */
    public function destroyKonten($id): JsonResponse
    {
        $konten = KontenMateri::findOrFail($id);
        $this->deleteFileByPublicUrl($konten->file_url);
        $konten->delete();

        return response()->json(['message' => 'Konten berhasil dihapus'], 200);
    }

    private function deleteFileByPublicUrl(?string $publicUrl): void
    {
        if (!$publicUrl) return;
        $publicPath = parse_url($publicUrl, PHP_URL_PATH);
        if (!$publicPath) return;

        $diskPath = ltrim(str_replace('/storage/', '', $publicPath), '/');
        if ($diskPath) {
            Storage::disk('public')->delete($diskPath);
        }
    }
}

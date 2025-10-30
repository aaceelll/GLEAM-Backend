<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\TestModel;
use App\Models\KontenMateri;
use App\Models\BankSoal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\File;

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
     * ===== ADMIN: DOWNLOAD KONTEN (AMAN UNTUK LINT) =====
     * GET /api/admin/materi/konten/{id}/download
     */
    
    public function downloadKonten($id): BinaryFileResponse
    {
        $konten = KontenMateri::findOrFail($id);
        if (!$konten->file_url) {
            abort(404, 'File tidak tersedia');
        }

        // contoh: https://api.gleam.id/storage/materi/Abc.pdf -> materi/Abc.pdf
        $publicPath = parse_url($konten->file_url, PHP_URL_PATH) ?? '';
        $relative   = ltrim(str_replace('/storage/', '', $publicPath), '/'); // materi/xxx.pdf

        if (!Storage::disk('public')->exists($relative)) {
            abort(404, 'File tidak ditemukan');
        }

        // path absolut ke storage/app/public/...
        $fullPath = Storage::disk('public')->path($relative);
        $niceName = Str::slug($konten->judul ?: 'materi') . '.pdf';
        $mime     = File::mimeType($fullPath) ?: 'application/pdf';

        // pakai response()->download supaya tidak merah di Intelephense
        return response()->download($fullPath, $niceName, [
            'Content-Type' => $mime,
        ]);
    }

    /**
     * ===== USER: LIST KONTEN + TES (BANKS) =====
     * GET /api/materi/konten?slug=diabetes-melitus
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

        // Daftar bank publish + punya soal
        $banks = BankSoal::query()
            ->where('status', 'publish')
            ->withCount('soal')
            ->having('soal_count', '>', 0)
            ->orderBy('nama')
            ->get(['id','nama','tipe']);

        $tesFormatted = $banks->map(function ($b) {
            return [
                'id'          => (int) $b->id,
                'nama'        => $b->nama,
                'deskripsi'   => null,
                'totalSoal'   => (int) $b->soal_count,
                'durasiMenit' => null,
                'bank_id'     => (int) $b->id,
                'source'      => 'banks',
                'tipe'        => $b->tipe ?? null,
            ];
        })->values();

        return response()->json(['konten' => $konten, 'tes' => $tesFormatted], 200);
    }

    /**
     * ===== USER: DETAIL TES (MODE TESTS legacy) =====
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
     * ===== USER: DETAIL TES (MODE BANKS) =====
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
        if (!$materi) {
            // aman: FE tetap dapat array kosong (tidak error)
            return response()->json(['data' => []], 200);
        }

        $konten = KontenMateri::where('materi_id', $materi->id)
            ->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $konten], 200);
    }

    /**
     * ===== ADMIN: TAMBAH KONTEN =====
     * POST /api/admin/materi/konten
     * Body: slug (opsional), judul, deskripsi, video_id (opsional), file_pdf (opsional)
     */
    public function storeKonten(Request $request): JsonResponse
    {
        $request->validate([
            'slug'      => 'nullable|string',
            'judul'     => 'required|string|max:255',
            'video_id'  => 'nullable|string',
            'file_pdf'  => 'nullable|file|mimes:pdf|max:10240',
            'deskripsi' => 'required|string',
        ]);

        $slug = $request->input('slug', 'diabetes-melitus');

        // fleksibel: buat materi jika belum ada
        $materi = Materi::firstOrCreate(
            ['slug' => $slug],
            ['nama' => ucwords(str_replace('-', ' ', $slug))]
        );

        // default: tanpa file
        $url = null;

        if ($request->hasFile('file_pdf')) {
            $path = $request->file('file_pdf')->store('materi', 'public'); // storage/app/public/materi/xxx.pdf
            $url  = asset(Storage::url($path)); // -> /storage/materi/xxx.pdf

            // konsisten: copy fisik ke public/storage/materi (untuk akses statis/cdn)
            $publicPath = public_path('storage/materi');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0775, true);
            }
            copy(storage_path('app/public/'.$path), $publicPath.'/'.basename($path));
        }

        $konten = KontenMateri::create([
            'materi_id' => $materi->id,
            'judul'     => $request->judul,
            'video_id'  => $request->input('video_id') ?: null,
            'file_url'  => $url,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json(['message' => 'Konten berhasil ditambahkan','data' => $konten], 201);
    }

    /**
     * ===== ADMIN: UPDATE KONTEN =====
     * PATCH /api/admin/materi/konten/{id}
     * Body: slug (opsional), judul, deskripsi, video_id (opsional), file_pdf (opsional)
     */
    public function updateKonten(Request $request, $id): JsonResponse
    {
        $request->validate([
            'slug'      => 'nullable|string',
            'judul'     => 'required|string|max:255',
            'video_id'  => 'nullable|string',
            'file_pdf'  => 'nullable|file|mimes:pdf|max:10240',
            'deskripsi' => 'required|string',
        ]);

        $konten = KontenMateri::findOrFail($id);

        if ($request->filled('slug')) {
            $materi = Materi::firstOrCreate(
                ['slug' => $request->input('slug')],
                ['nama' => ucwords(str_replace('-', ' ', $request->input('slug')))]
            );
            $konten->materi_id = $materi->id;
        }

        if ($request->hasFile('file_pdf')) {
            // hapus file lama dari storage (kalau ada)
            $this->deleteFileByPublicUrl($konten->file_url);

            // simpan baru ke disk 'public'
            $newPath = $request->file('file_pdf')->store('materi', 'public');
            $konten->file_url = asset(Storage::url($newPath));

            // konsisten: copy fisik ke public/storage/materi
            $publicPath = public_path('storage/materi');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0775, true);
            }
            copy(storage_path('app/public/'.$newPath), $publicPath.'/'.basename($newPath));
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

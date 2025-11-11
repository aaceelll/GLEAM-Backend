<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\KontenMateri;
use Illuminate\Http\Request;
use App\Models\BankSoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\File;

class MateriController extends Controller
{
    // GET /api/admin/materi
    public function index(Request $r): JsonResponse
    {
        $q = Materi::query()->orderBy('nama');
        if ($slug = $r->query('slug')) {
            $q->where('slug', $slug);
        }
        return response()->json($q->get());
    }

    // GET /api/admin/materi/konten/{id}/download
    public function downloadKonten($id): BinaryFileResponse
    {
        $konten = KontenMateri::findOrFail($id);
        if (!$konten->file_url) {
            abort(404, 'File tidak tersedia');
        }

        // contoh: https://api.gleam.id/storage/materi/Abc.pdf 
        $publicPath = parse_url($konten->file_url, PHP_URL_PATH) ?? '';
        $relative   = ltrim(str_replace('/storage/', '', $publicPath), '/'); 

        if (!Storage::disk('public')->exists($relative)) {
            abort(404, 'File tidak ditemukan');
        }

        // path storage/app/public/...
        $fullPath = Storage::disk('public')->path($relative);
        $niceName = Str::slug($konten->judul ?: 'materi') . '.pdf';
        $mime     = File::mimeType($fullPath) ?: 'application/pdf';

        return response()->download($fullPath, $niceName, [
            'Content-Type' => $mime,
        ]);
    }

    // GET /api/admin/materi/konten?
    public function listKonten(Request $request): JsonResponse
    {
        $slug = $request->query('slug', 'diabetes-melitus');

        $materi = Materi::where('slug', $slug)->first();
        if (!$materi) {
            return response()->json(['data' => []], 200);
        }

        $konten = KontenMateri::where('materi_id', $materi->id)
            ->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $konten], 200);
    }

    // POST /api/admin/materi/konten
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
            $url  = asset(Storage::url($path)); 
 
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

    // Publish konten materi dan bank soal ke user 
    public function listKontenPublic(Request $request): JsonResponse
    {
        $slug = $request->query('slug', 'diabetes-melitus');

        $materi = Materi::where('slug', $slug)->first();
        if (!$materi) {
            return response()->json(['konten' => [], 'tes' => []], 200);
        }

        $konten = KontenMateri::where('materi_id', $materi->id)
            ->orderBy('created_at', 'desc')
            ->get(['id','judul','video_id','file_url','deskripsi','created_at','updated_at']);

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

    // publish list soal di user
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

    // GET /api/materi/tes/{id}
    public function showTesPublic($id): JsonResponse
    {
        $bankId = (int) $id;

        $bank = BankSoal::with(['soal' => function ($q) {
            $q->select('id','bank_id','teks','tipe','opsi','bobot','kunci');
        }])->findOrFail($bankId);

        return response()->json([
            'bank_id'   => (int) $bank->id,
            'nama'      => $bank->nama,
            'totalSoal' => $bank->soal->count(),
            'soal'      => $bank->soal,
            'source'    => 'banks',   
        ], 200);
    }

    // PATCH /api/admin/materi/konten/{id}
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

    // DELETE /api/admin/materi/konten/{id}
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
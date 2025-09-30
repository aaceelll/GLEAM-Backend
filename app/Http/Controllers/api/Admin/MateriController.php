<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\KontenMateri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MateriController extends Controller
{
    /**
     * ===== MASTER MATERI (ADMIN OPSIONAL) =====
     * GET /api/admin/materi?slug=diabetes-melitus
     */
    public function index(Request $r)
    {
        $q = Materi::query()->orderBy('nama');
        if ($slug = $r->query('slug')) {
            $q->where('slug', $slug);
        }
        return response()->json($q->get());
    }

    /**
     * ===== USER: LIST KONTEN =====
     * GET /api/materi/konten?slug=diabetes-melitus
     */
    public function listKontenPublic(Request $request)
    {
        $slug = $request->query('slug', 'diabetes-melitus');

        $materi = Materi::where('slug', $slug)->first();
        if (!$materi) {
            return response()->json(['data' => []], 200);
        }

        $konten = KontenMateri::where('materi_id', $materi->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'judul', 'video_id', 'file_url', 'deskripsi', 'created_at', 'updated_at']);

        return response()->json(['data' => $konten], 200);
    }

    /**
     * ===== ADMIN: LIST KONTEN =====
     * GET /api/admin/materi/konten?slug=diabetes-melitus
     */
    public function listKonten(Request $request)
    {
        $slug = $request->query('slug', 'diabetes-melitus');

        $materi = Materi::where('slug', $slug)->first();
        if (!$materi) {
            return response()->json(['data' => []], 200);
        }

        $konten = KontenMateri::where('materi_id', $materi->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $konten], 200);
    }

    /**
     * ===== ADMIN: TAMBAH KONTEN =====
     * POST /api/admin/materi/konten
     */
    public function storeKonten(Request $request)
    {
        $request->validate([
            'judul'     => 'required|string|max:255',
            'video_id'  => 'nullable|string',
            'file_pdf'  => 'required|file|mimes:pdf|max:10240',
            'deskripsi' => 'required|string',
        ]);

        $materi = Materi::where('slug', 'diabetes-melitus')->first();
        if (!$materi) {
            return response()->json(['message' => 'Materi tidak ditemukan'], 404);
        }

        $path = $request->file('file_pdf')->store('materi', 'public');
        $url  = asset(Storage::url($path)); // absolute URL

        $konten = KontenMateri::create([
            'materi_id' => $materi->id,
            'judul'     => $request->judul,
            'video_id'  => $request->video_id,
            'file_url'  => $url,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'message' => 'Konten berhasil ditambahkan',
            'data'    => $konten,
        ], 201);
    }

    /**
     * ===== ADMIN: UPDATE KONTEN =====
     * PATCH /api/admin/materi/konten/{id}
     */
    public function updateKonten(Request $request, $id)
    {
        $request->validate([
            'judul'     => 'required|string|max:255',
            'video_id'  => 'nullable|string',
            'file_pdf'  => 'nullable|file|mimes:pdf|max:10240',
            'deskripsi' => 'required|string',
        ]);

        $konten = KontenMateri::findOrFail($id);

        if ($request->hasFile('file_pdf')) {
            // hapus file lama
            $publicPath = parse_url($konten->file_url, PHP_URL_PATH);
            $diskPath   = ltrim(str_replace('/storage/', '', $publicPath), '/');
            if ($diskPath) {
                Storage::disk('public')->delete($diskPath);
            }

            $newPath = $request->file('file_pdf')->store('materi', 'public');
            $konten->file_url = asset(Storage::url($newPath));
        }

        $konten->judul     = $request->judul;
        $konten->video_id  = $request->video_id;
        $konten->deskripsi = $request->deskripsi;
        $konten->save();

        return response()->json([
            'message' => 'Konten berhasil diperbarui',
            'data'    => $konten,
        ], 200);
    }

    /**
     * ===== ADMIN: HAPUS KONTEN =====
     * DELETE /api/admin/materi/konten/{id}
     */
    public function destroyKonten($id)
    {
        $konten = KontenMateri::findOrFail($id);

        $publicPath = parse_url($konten->file_url, PHP_URL_PATH);
        $diskPath   = ltrim(str_replace('/storage/', '', $publicPath), '/');
        if ($diskPath) {
            Storage::disk('public')->delete($diskPath);
        }

        $konten->delete();

        return response()->json(['message' => 'Konten berhasil dihapus'], 200);
    }
}

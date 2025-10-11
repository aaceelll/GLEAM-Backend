<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use Illuminate\Http\Request;

class MateriController extends Controller
{
    // GET /api/materi/konten?slug=diabetes-melitus
    public function konten(Request $request)
    {
        $slug = $request->query('slug');
        $materi = Materi::where('slug', $slug)->firstOrFail();

        // konten materi (sesuaikan field)
        $konten = $materi->kontenMateri()
            ->select(['id','judul','deskripsi','video_id','file_url','created_at','updated_at'])
            ->orderBy('urutan')
            ->get();

        // bank soal yang ter-link ke materi ini, status publish & punya minimal 1 soal
        $tes = $materi->bankSoal()
            ->where('question_banks.status', 'publish')
            ->whereHas('soal')
            ->withCount('soal')
            ->orderBy('materi_bank_soal.urutan')
            ->get()
            ->map(function ($b) {
                return [
                    'id'         => $b->id,
                    'nama'       => $b->nama,
                    'deskripsi'  => null,
                    'totalSoal'  => (int)$b->soal_count,
                    'durasiMenit'=> null,
                    'bank_id'    => $b->id,
                    'source'     => 'banks',
                ];
            });

        return response()->json([
            'data' => [
                'konten' => $konten,
                'tes'    => $tes,
            ]
        ]);
    }
}

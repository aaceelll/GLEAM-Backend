<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;

class QuizController extends Controller
{
    /**
     * List bank kuisioner untuk user:
     * - status publish
     * - punya minimal 1 soal
     */
    public function banksDefault()
    {
        $banks = BankSoal::query()
            ->where('status', 'publish')
            ->withCount('soal')
            ->having('soal_count', '>', 0)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (BankSoal $b) => [
                'id'        => $b->id,
                'nama'      => $b->nama,
                'tipe'      => $b->tipe ?? null,   // pre/post kalau ada
                'totalSoal' => $b->soal_count,
                'status'    => $b->status,
                'updatedAt' => optional($b->updated_at)->toDateTimeString(),
            ]);

        return response()->json(['data' => $banks]);
    }

    public function listSoalPublic(BankSoal $bank)
    {
        $items = $bank->soal()
            ->orderBy('id')
            ->get(['id','teks','tipe','opsi','bobot']);
        return response()->json(['data' => $items]);
    }
}

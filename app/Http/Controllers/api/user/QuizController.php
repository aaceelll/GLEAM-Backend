<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\BankSoal;

class QuizController extends Controller
{
    public function banksDefault()
    {
        $materi = Materi::where('slug', config('gleam.default_materi_slug', 'diabetes-melitus'))->firstOrFail();

        $banks = $materi->bankSoal()
            ->wherePivot('is_active', true)
            ->get()
            ->map(fn (BankSoal $b) => [
                'id'        => $b->id,
                'nama'      => $b->nama,
                'tipe'      => $b->pivot->tipe,
                'totalSoal' => $b->soal()->count(),
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

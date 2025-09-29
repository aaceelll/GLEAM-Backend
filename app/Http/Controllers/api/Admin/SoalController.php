<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SoalController extends Controller
{
    public function listByBank($bankId)
    {
        $bank = BankSoal::findOrFail($bankId);
        $items = $bank->soal()->orderBy('id')
            ->get(['id','teks','tipe','bobot','kunci']);
        return response()->json($items);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'bankId' => 'required|exists:question_banks,id',
            'teks'   => 'required|string',
            'tipe'   => ['required', Rule::in(['true_false','pilihan_ganda'])],
            'bobot'  => 'nullable|integer|min:1',
            'opsi'   => 'nullable|array',
            'kunci'  => 'nullable|string',
        ]);

        if ($data['tipe'] === 'true_false') {
            $data['kunci'] = in_array($data['kunci'], ['true','false']) ? $data['kunci'] : 'true';
            $data['opsi'] = null;
        }

        $soal = Soal::create([
            'bank_id' => $data['bankId'],
            'teks'    => $data['teks'],
            'tipe'    => $data['tipe'],
            'bobot'   => $data['bobot'] ?? 1,
            'opsi'    => $data['opsi'] ?? null,
            'kunci'   => $data['kunci'] ?? null,
        ]);

        return response()->json($soal, 201);
    }

    public function destroy($id)
    {
        Soal::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}

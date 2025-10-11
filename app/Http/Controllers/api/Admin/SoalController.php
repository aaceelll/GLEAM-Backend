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

        $items = $bank->soal()
            ->orderBy('id')
            ->get(['id','bank_id','teks','tipe','bobot','kunci','opsi','created_at','updated_at']);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, $bankId = null)
    {
        $bankId = $bankId ?? $request->input('bank_id') ?? $request->input('bankId');

        if (!$bankId) {
            return response()->json(['message' => 'The bank id field is required.'], 422);
        }

        $validated = $request->validate([
            'teks'         => 'required|string',
            'tipe'         => ['required', Rule::in(['pilihan_ganda','screening','true_false'])],
            'bobot'        => 'nullable|integer|min:1',
            'kunci'        => 'nullable|string',
            'opsi'         => 'array',
            'opsi.*.no'    => 'nullable|integer',
            'opsi.*.teks'  => 'required_with:opsi|string',
            'opsi.*.skor'  => 'nullable|numeric',
        ]);

        $tipe  = $validated['tipe'];
        $opsi  = $request->input('opsi', []);
        $kunci = $request->input('kunci');

        if ($tipe === 'screening') {
            $opsi = [];
        } elseif ($tipe === 'true_false') {
            $opsi  = [
                ['no' => 1, 'teks' => 'True',  'skor' => 1],
                ['no' => 2, 'teks' => 'False', 'skor' => 0],
            ];
            $kunci = in_array($kunci, ['true','false'], true) ? $kunci : 'true';
        }

        $soal = Soal::create([
            'bank_id' => (int) $bankId,
            'teks'    => $validated['teks'],
            'tipe'    => $tipe,
            'bobot'   => $request->input('bobot', 1),
            'opsi'    => $opsi,
            'kunci'   => $kunci,
        ]);

        // sabuk pengaman
        Soal::syncBankVisibility((int)$bankId);

        return response()->json(['data' => $soal], 201);
    }

    public function destroy($id)
    {
        $soal   = Soal::findOrFail($id);
        $bankId = (int)$soal->bank_id;

        $soal->delete();

        // sabuk pengaman
        if ($bankId) {
            Soal::syncBankVisibility($bankId);
        }

        return response()->json(['ok' => true]);
    }
}

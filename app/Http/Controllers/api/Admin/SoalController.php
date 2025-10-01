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

        // Pastikan opsi ikut ter-load
        $items = $bank->soal()
            ->orderBy('id')
            ->get(['id', 'bank_id', 'teks', 'tipe', 'bobot', 'kunci', 'opsi', 'created_at', 'updated_at']);

        return response()->json($items);
    }

    public function store(Request $request, $bankId = null)
    {
        // Ambil bankId dari route atau body (bank_id/bankId)
        $bankId = $bankId ?? $request->input('bank_id') ?? $request->input('bankId');

        if (!$bankId) {
            return response()->json(['message' => 'The bank id field is required.'], 422);
        }

        // Validasi dasar
        $validated = $request->validate([
            'teks'         => 'required|string',
            'tipe'         => ['required', Rule::in(['pilihan_ganda', 'screening', 'true_false'])],
            'bobot'        => 'nullable|integer|min:1',
            'kunci'        => 'nullable|string',
            'opsi'         => 'array',
            'opsi.*.no'    => 'nullable|integer',
            'opsi.*.teks'  => 'required_with:opsi|string',
            'opsi.*.skor'  => 'nullable|numeric',
        ]);

        $tipe = $validated['tipe'];

        // Normalisasi opsi sesuai tipe
        $opsi = $request->input('opsi', []);

        if ($tipe === 'screening') {
            $opsi = []; // screening tanpa opsi
        }

        if ($tipe === 'true_false') {
            // Opsi default true/false + kunci opsional
            $opsi = [
                ['no' => 1, 'teks' => 'True',  'skor' => 1],
                ['no' => 2, 'teks' => 'False', 'skor' => 0],
            ];
            $kunci = in_array($request->input('kunci'), ['true', 'false']) ? $request->input('kunci') : 'true';
        } else {
            $kunci = $request->input('kunci');
        }

        $soal = Soal::create([
            'bank_id' => (int) $bankId,
            'teks'    => $validated['teks'],
            'tipe'    => $tipe,
            'bobot'   => $request->input('bobot', 1),
            'opsi'    => $opsi,
            'kunci'   => $kunci,
        ]);

        return response()->json(['data' => $soal], 201);
    }

    public function destroy($id)
    {
        Soal::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}

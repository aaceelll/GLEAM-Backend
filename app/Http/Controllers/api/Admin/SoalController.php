<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Soal;
use Illuminate\Http\Request;

class SoalController extends Controller
{
    // GET /api/admin/bank-soal/{bankId}/soal
    public function listByBank($bankId)
    {
        $bank = BankSoal::findOrFail($bankId);

        $items = $bank->soal()
            ->orderBy('id')
            ->get(['id','bank_id','teks','bobot','opsi','created_at','updated_at']);

        return response()->json(['data' => $items]);
    }

    // POST /api/admin/bank-soal/{bankId}/soal
    public function store(Request $request, $bankId = null)
    {
        $bankId = $bankId ?? $request->input('bank_id');

        if (!$bankId) {
            return response()->json(['message' => 'The bank id field is required.'], 422);
        }

        $validated = $request->validate([
            'teks'         => 'required|string',
            'bobot'        => 'nullable|integer|min:1',
            'opsi'         => 'array',
            'opsi.*.no'    => 'required|integer',
            'opsi.*.teks'  => 'required_with:opsi|string',
            'opsi.*.skor'  => 'required|numeric',
        ]);

        $soal = Soal::create([
            'bank_id' => (int) $bankId,
            'teks'    => $validated['teks'],
            'tipe'    => 'pilihan_ganda',
            'bobot'   => $request->input('bobot', 1),
            'opsi'    => $validated['opsi'],
        ]);

        return response()->json(['data' => $soal], 201);
    }

    // DELETE /api/admin/soal/{id}
    public function destroy($id)
    {
        $soal   = Soal::findOrFail($id);
        $soal->delete();

        return response()->json(['ok' => true]);
    }
}

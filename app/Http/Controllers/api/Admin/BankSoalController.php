<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use Illuminate\Http\Request;

class BankSoalController extends Controller
{
    public function index()
    {
        $banks = BankSoal::withCount('soal')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'nama' => $b->nama,
                'status' => $b->status,
                'totalSoal' => $b->soal_count,
                'updatedAt' => $b->updated_at,
            ]);

        return response()->json($banks);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nama' => 'required|string|max:255',
            'status' => 'nullable|in:draft,publish',
        ]);
        $data['status'] = $data['status'] ?? 'draft';
        $bank = BankSoal::create($data);

        return response()->json($bank, 201);
    }

    public function update($id, Request $r)
    {
        $bank = BankSoal::findOrFail($id);
        $bank->update($r->validate([
            'nama' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:draft,publish',
        ]));
        return response()->json(['ok' => true]);
    }

    public function destroy($id)
    {
        BankSoal::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}

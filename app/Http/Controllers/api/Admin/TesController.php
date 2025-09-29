<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materi;
use App\Models\TestModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TesController extends Controller
{
    public function index(Request $r)
    {
        $q = TestModel::query()->orderByDesc('updated_at');
        if ($r->filled('bankId')) $q->where('bank_id', $r->integer('bankId'));
        $tests = $q->get(['id','nama','tipe','status']);
        return response()->json($tests);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'nama'      => 'required|string|max:255',
            'tipe'      => ['required', Rule::in(['pre','post'])],
            'materiId'  => 'required|exists:materi,id',
            'bankId'    => 'required|exists:question_banks,id',
            'status'    => 'nullable|in:draft,publish',
        ]);
        $data['status'] = $data['status'] ?? 'draft';

        $test = TestModel::create([
            'nama' => $data['nama'],
            'tipe' => $data['tipe'],
            'materi_id' => $data['materiId'],
            'bank_id'   => $data['bankId'],
            'status'    => $data['status'],
        ]);

        return response()->json($test, 201);
    }

    public function update($id, Request $r)
    {
        $test = TestModel::findOrFail($id);
        $test->update($r->validate([
            'nama'   => 'sometimes|string|max:255',
            'tipe'   => ['sometimes', Rule::in(['pre','post'])],
            'status' => 'sometimes|in:draft,publish',
        ]));
        return response()->json(['ok' => true]);
    }

    public function destroy($id)
    {
        TestModel::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}

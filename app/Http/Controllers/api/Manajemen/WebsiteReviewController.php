<?php

namespace App\Http\Controllers\Api\Manajemen;

use App\Http\Controllers\Controller;
use App\Models\WebsiteReview;
use Illuminate\Http\Request;

class WebsiteReviewController extends Controller
{
    // List + join user (simple pagination)
    public function index(Request $request)
    {
        $q = WebsiteReview::query()
            ->with(['user:id,nama,email,role'])
            ->when($request->filled('search'), function ($qq) use ($request) {
                $s = $request->get('search');
                $qq->whereHas('user', function ($u) use ($s) {
                    $u->where('nama', 'like', "%$s%")
                      ->orWhere('email', 'like', "%$s%");
                });
            })
            ->orderByDesc('updated_at');

        $perPage = (int)($request->get('per_page', 10));
        $data = $q->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // Detail single review
    public function show($id)
    {
        $review = WebsiteReview::with(['user:id,nama,email'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $review,
        ]);
    }

    // Riwayat ulasan berdasarkan user_id
    public function userHistory($userId)
    {
        $reviews = WebsiteReview::where('user_id', $userId)
            ->with(['user:id,nama,email'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $reviews,
        ]);
    }

    // // Ringkasan statistik sederhana (rata2 tiap butir + total respon)
    // public function stats()
    // {
    //     // contoh skor "SUS-like" sangat sederhana (opsional)
    //     $all = WebsiteReview::get(['q1','q2','q3','q4','q5','q6','q7','q8','q9','q10']);
    //     $sus = null;
    //     if ($all->count() > 0) {
    //         $scores = $all->map(function ($r) {
    //             $vals = collect([$r->q1,$r->q2,$r->q3,$r->q4,$r->q5,$r->q6,$r->q7,$r->q8,$r->q9,$r->q10])
    //                     ->filter(fn($v) => $v !== null)
    //                     ->map(fn($v) => max(0, min(4, $v - 1))); // 1..5 -> 0..4
    //             return $vals->sum();
    //         });
    //         $sus = round(($scores->avg() / 40) * 100, 1);
    //     }
    // }

    // Opsional: hapus
    public function destroy($id)
    {
        $r = WebsiteReview::findOrFail($id);
        $r->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ulasan dihapus',
        ]);
    }
}
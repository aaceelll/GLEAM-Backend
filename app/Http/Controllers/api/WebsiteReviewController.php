<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebsiteReviewController extends Controller
{
    /**
     * Ambil ULASAN TERBARU milik user login (untuk prefill, dsb).
     */
    public function show()
    {
        $review = WebsiteReview::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $review,
        ]);
    }

    /**
     * Simpan ULASAN BARU SELALU (INSERT) – tidak overwrite.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'q1' => 'nullable|integer|min:1|max:5',
            'q2' => 'nullable|integer|min:1|max:5',
            'q3' => 'nullable|integer|min:1|max:5',
            'q4' => 'nullable|integer|min:1|max:5',
            'q5' => 'nullable|integer|min:1|max:5',
            'q6' => 'nullable|integer|min:1|max:5',
            'q7' => 'nullable|integer|min:1|max:5',
            'q8' => 'nullable|integer|min:1|max:5',
            'q9' => 'nullable|integer|min:1|max:5',
            'q10' => 'nullable|integer|min:1|max:5',
            'suggestion' => 'nullable|string|max:5000',
        ]);

        $review = WebsiteReview::create([
            'user_id' => Auth::id(),
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil dikirim',
            'data' => $review,
        ], 201);
    }

    /**
     * (Opsional) History milik user login sendiri.
     */
    public function history()
    {
        $reviews = WebsiteReview::with('user:id,nama,email')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }
}

// tes
<?php

namespace app\Http\Controllers\api\Manajemen;

use App\Http\Controllers\Controller;
use App\Models\WebsiteReview;
use Illuminate\Http\Request;

class WebsiteReviewController extends Controller
{
    // GET  /api/website-reviews
    public function index(Request $request)
    {
        // ambil review terakhir per user
        $sub = WebsiteReview::selectRaw('MAX(id) as id')
            ->groupBy('user_id');

        // pencarian user
        $q = WebsiteReview::query()
            ->whereIn('id', $sub)
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

    // GET /api/website-reviews/user/{userId}/history
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

    //GET /api/website-reviews/{id}
    public function show($id)
    {
        $review = WebsiteReview::with(['user:id,nama,email'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $review,
        ]);
    }
}
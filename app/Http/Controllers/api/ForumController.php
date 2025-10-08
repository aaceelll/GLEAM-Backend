<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ForumCategory;
use App\Models\ForumThread;
use App\Models\ForumReply;
use App\Models\ForumThreadLike;
use App\Models\ForumReplyLike;

class ForumController extends Controller
{
    // Get categories
    public function getCategories()
    {
        $categories = ForumCategory::orderBy('name')->get();
        return response()->json($categories);
    }

    // Get threads with filter
    public function getThreads(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $type = $request->query('type', 'public'); // 'public' or 'private'

        $query = ForumThread::with(['user', 'category', 'assignedNakes']);

        if ($type === 'public') {
            $query->where('is_private', false);
        } else {
            $query->where('is_private', true);
            if ($user->role !== 'admin') {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('assigned_nakes_id', $user->id);
                });
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('content', 'like', "%{$s}%");
            });
        }

        $query->orderBy('is_pinned', 'desc')
              ->orderBy('last_activity_at', 'desc');

        $threads = $query->paginate(15);

        foreach ($threads as $t) {
            $t->is_liked = ForumThreadLike::where('thread_id', $t->id)
                ->where('user_id', $user->id)->exists();
        }

        return response()->json($threads);
    }

    // Get thread detail
    public function getThreadDetail(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $thread = ForumThread::with(['user', 'category', 'assignedNakes', 'replies.user', 'replies.likes'])
            ->findOrFail($id);

        if ($thread->is_private) {
            if ($user->role === 'admin') {
                return response()->json(['message' => 'Admin tidak memiliki akses ke pertanyaan private'], 403);
            }
            if ($user->role === 'user' && $thread->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if ($user->role === 'nakes' && $thread->assigned_nakes_id !== $user->id && $thread->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $thread->increment('view_count');

        $thread->is_liked = ForumThreadLike::where('thread_id', $id)
            ->where('user_id', $user->id)->exists();

        foreach ($thread->replies as $reply) {
            $reply->is_liked = ForumReplyLike::where('reply_id', $reply->id)
                ->where('user_id', $user->id)->exists();
        }

        return response()->json($thread);
    }

    // Create thread (category optional)
    public function createThread(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:forum_categories,id',
            'title'       => 'required|max:255',
            'content'     => 'required',
            'is_private'  => 'boolean',
        ]);

        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        // Fallback kategori "Umum" (buat kalau kosong)
        $categoryId = $validated['category_id'] ?? null;
        if (!$categoryId) {
            $category = ForumCategory::where('slug', 'umum')->first()
                ?: ForumCategory::where('name', 'like', '%umum%')->first()
                ?: ForumCategory::first();

            if (!$category) {
                $category = ForumCategory::create(['name' => 'Umum', 'slug' => 'umum']);
            }
            $categoryId = $category->id;
        }

        $thread = ForumThread::create([
            'user_id'         => $user->id,
            'category_id'     => $categoryId,
            'title'           => $validated['title'],
            'content'         => $validated['content'],
            'is_private'      => $validated['is_private'] ?? false,
            'last_activity_at'=> now(),
        ]);

        ForumCategory::where('id', $categoryId)->increment('thread_count');

        return response()->json([
            'message' => $thread->is_private ? 'Pertanyaan private berhasil dikirim' : 'Thread berhasil dibuat',
            'thread'  => $thread->load('user', 'category')
        ], 201);
    }

    // Reply to thread
    public function replyThread(Request $request, $id)
    {
        $validated = $request->validate(['content' => 'required']);
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $thread = ForumThread::findOrFail($id);

        if ($thread->is_private) {
            if ($user->role === 'admin') return response()->json(['message' => 'Admin tidak bisa reply ke pertanyaan private'], 403);
            if ($user->role === 'user' && $thread->user_id !== $user->id) return response()->json(['message' => 'Unauthorized'], 403);
            if ($user->role === 'nakes' && $thread->assigned_nakes_id !== $user->id) return response()->json(['message' => 'Anda belum mengambil pertanyaan ini'], 403);
        }

        if ($thread->is_locked) return response()->json(['message' => 'Thread sudah dikunci'], 403);

        $reply = ForumReply::create([
            'thread_id'      => $id,
            'user_id'        => $user->id,
            'content'        => $validated['content'],
            'responder_role' => $user->role,
        ]);

        $thread->increment('reply_count');
        $thread->update(['last_activity_at' => now()]);

        return response()->json(['message' => 'Balasan berhasil dikirim', 'reply' => $reply->load('user')], 201);
    }

    // Like thread
    public function likeThread(Request $request, $id)
    {
        $thread = ForumThread::findOrFail($id);
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $like = ForumThreadLike::where('thread_id', $id)->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $thread->decrement('like_count');
            $liked = false;
        } else {
            ForumThreadLike::create(['thread_id' => $id, 'user_id' => $user->id]);
            $thread->increment('like_count');
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'like_count' => $thread->like_count]);
    }

    // Like reply
    public function likeReply(Request $request, $id)
    {
        $reply = ForumReply::findOrFail($id);
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $like = ForumReplyLike::where('reply_id', $id)->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $reply->decrement('like_count');
            $liked = false;
        } else {
            ForumReplyLike::create(['reply_id' => $id, 'user_id' => $user->id]);
            $reply->increment('like_count');
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'like_count' => $reply->like_count]);
    }

    // Delete thread
    public function deleteThread(Request $request, $id)
    {
        $thread = ForumThread::findOrFail($id);
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        if ($thread->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->role === 'admin' && $thread->is_private) {
            return response()->json(['message' => 'Admin tidak bisa menghapus pertanyaan private'], 403);
        }

        $thread->delete();
        return response()->json(['message' => 'Thread berhasil dihapus']);
    }

    // Delete reply
    public function deleteReply(Request $request, $id)
    {
        $reply = ForumReply::findOrFail($id);
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        if ($reply->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->role === 'admin' && $reply->thread->is_private) {
            return response()->json(['message' => 'Admin tidak bisa menghapus balasan di pertanyaan private'], 403);
        }

        $reply->delete();
        $reply->thread->decrement('reply_count');

        return response()->json(['message' => 'Balasan berhasil dihapus']);
    }

    // ===== NAKES ONLY =====
    public function getPendingPrivateThreads()
    {
        $threads = ForumThread::where('is_private', true)
            ->with(['user', 'category'])
            ->whereNull('assigned_nakes_id')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($threads);
    }

    public function getMyPrivateThreads(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $threads = ForumThread::where('is_private', true)
            ->with(['user', 'category', 'replies.user'])
            ->where('assigned_nakes_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($threads);
    }

    public function assignToSelf(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $thread = ForumThread::findOrFail($id);

        if (!$thread->is_private) return response()->json(['message' => 'Thread ini bukan pertanyaan private'], 400);
        if ($thread->assigned_nakes_id) return response()->json(['message' => 'Pertanyaan sudah diambil oleh nakes lain'], 400);

        $thread->update(['assigned_nakes_id' => $user->id]);

        return response()->json(['message' => 'Pertanyaan berhasil diambil']);
    }

    // ===== ADMIN ONLY (public only) =====
    public function pinThread($id)
    {
        $thread = ForumThread::findOrFail($id);
        if ($thread->is_private) return response()->json(['message' => 'Tidak bisa pin pertanyaan private'], 403);

        $thread->is_pinned = !$thread->is_pinned;
        $thread->save();

        return response()->json(['message' => 'Thread ' . ($thread->is_pinned ? 'dipin' : 'unpin')]);
    }

    public function lockThread($id)
    {
        $thread = ForumThread::findOrFail($id);
        if ($thread->is_private) return response()->json(['message' => 'Tidak bisa lock pertanyaan private'], 403);

        $thread->is_locked = !$thread->is_locked;
        $thread->save();

        return response()->json(['message' => 'Thread ' . ($thread->is_locked ? 'dikunci' : 'dibuka')]);
    }

    public function forceDeleteThread($id)
    {
        $thread = ForumThread::findOrFail($id);
        if ($thread->is_private) return response()->json(['message' => 'Tidak bisa force delete pertanyaan private'], 403);

        $thread->delete();
        return response()->json(['message' => 'Thread berhasil dihapus']);
    }
}

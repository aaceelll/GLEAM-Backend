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
    // ============================================================================
    // CATEGORIES
    // ============================================================================
    
    /**
     * Get all forum categories
     */
    public function getCategories()
    {
        $categories = ForumCategory::orderBy('name')->get();
        return response()->json($categories);
    }

    
    // THREADS - LIST & DETAIL
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

        // Search filter
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        $threads = $query->orderBy('is_pinned', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->get();

        return response()->json(['data' => $threads]);
    }

    /**
     * Get thread detail
     */
    public function getThreadDetail(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $thread = ForumThread::with([
            'user',
            'category',
            'assignedNakes',
            'replies.user'
        ])->findOrFail($id);

        // Permission check for private threads
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

        // Increment view count
        $thread->increment('view_count');

        // Add is_liked flag for thread
        $thread->is_liked = ForumThreadLike::where('thread_id', $id)
            ->where('user_id', $user->id)->exists();

        // Add is_liked flag for each reply
        foreach ($thread->replies as $reply) {
            $reply->is_liked = ForumReplyLike::where('reply_id', $reply->id)
                ->where('user_id', $user->id)->exists();
        }

        return response()->json($thread);
    }

    // ============================================================================
    // THREADS - CREATE, DELETE
    // ============================================================================
    
    /**
     * Create new thread (public or private)
     */
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

        // Fallback kategori "Umum" jika tidak ada category_id
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

    // Delete Forum
    public function deleteThread(Request $request, $id)
    {
        $thread = ForumThread::findOrFail($id);
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        // Permission check
        if ($thread->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->role === 'admin' && $thread->is_private) {
            return response()->json(['message' => 'Admin tidak bisa menghapus pertanyaan private'], 403);
        }

        $thread->delete();
        return response()->json(['message' => 'Thread berhasil dihapus']);
    }

    // ============================================================================
    // REPLIES - CREATE, DELETE
    // ============================================================================
    
    /**
     * Reply to thread
     * - Nakes BISA reply tanpa harus assigned dulu
     * - Auto-assign ke nakes yang reply pertama kali
     */
    public function replyThread(Request $request, $id)
    {
        $validated = $request->validate(['content' => 'required']);
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $thread = ForumThread::findOrFail($id);

        if ($thread->is_private) {
            // Admin tidak bisa reply private
            if ($user->role === 'admin') {
                return response()->json(['message' => 'Admin tidak bisa reply ke pertanyaan private'], 403);
            }
            
            // User hanya bisa reply pertanyaan sendiri
            if ($user->role === 'user' && $thread->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            // Nakes BISA reply meski belum di-assign
            // Pertanyaan akan otomatis di-assign ke nakes yang reply pertama kali
            if ($user->role === 'nakes') {
                if (!$thread->assigned_nakes_id) {
                    $thread->update(['assigned_nakes_id' => $user->id]);
                }
            }
        }

        if ($thread->is_locked) {
            return response()->json(['message' => 'Thread sudah dikunci'], 403);
        }

        $reply = ForumReply::create([
            'thread_id'      => $id,
            'user_id'        => $user->id,
            'content'        => $validated['content'],
            'responder_role' => $user->role,
        ]);

        $thread->increment('reply_count');
        $thread->update(['last_activity_at' => now()]);

        return response()->json([
            'message' => 'Balasan berhasil dikirim', 
            'reply' => $reply->load('user'),
            'thread' => $thread->fresh(['assignedNakes'])
        ], 201);
    }

    /**
     * Delete reply (only owner or admin)
     */
    public function deleteReply(Request $request, $id)
    {
        $reply = ForumReply::findOrFail($id);
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        // Permission check
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

    // ============================================================================
    // LIKES - THREAD & REPLY
    // ============================================================================
    
    /**
     * Like/Unlike thread
     */
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

    /**
     * Like/Unlike reply
     */
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

    // ============================================================================
    // NAKES ONLY - PRIVATE QUESTIONS
    // ============================================================================
    
    /**
     * Get pending private threads (belum di-assign)
     */
    public function getPendingPrivateThreads()
    {
        $threads = ForumThread::where('is_private', true)
            ->with(['user', 'category'])
            ->whereNull('assigned_nakes_id')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($threads);
    }

    /**
     * Get my assigned private threads
     */
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

    /**
     * Assign private thread to self (nakes)
     */
    public function assignToSelf(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $thread = ForumThread::findOrFail($id);

        if (!$thread->is_private) {
            return response()->json(['message' => 'Thread ini bukan pertanyaan private'], 400);
        }
        if ($thread->assigned_nakes_id) {
            return response()->json(['message' => 'Pertanyaan sudah diambil oleh nakes lain'], 400);
        }

        $thread->update(['assigned_nakes_id' => $user->id]);

        return response()->json(['message' => 'Pertanyaan berhasil diambil']);
    }

    /**
     * 🆕 Toggle Lock/Unlock private thread (nakes)
     */
    public function toggleLockThread(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);
        
        $thread = ForumThread::findOrFail($id);
        
        // Validasi: Hanya nakes yang di-assign yang bisa lock/unlock
        if ($user->role === 'nakes') {
            if ($thread->is_private && $thread->assigned_nakes_id !== $user->id) {
                return response()->json(['message' => 'Anda tidak bisa mengubah status pertanyaan ini'], 403);
            }
        }
        
        // Toggle lock status
        $thread->update(['is_locked' => !$thread->is_locked]);
        
        $message = $thread->is_locked 
            ? 'Pertanyaan berhasil dikunci' 
            : 'Pertanyaan berhasil dibuka kembali';
        
        return response()->json([
            'message' => $message,
            'thread' => $thread->fresh()
        ]);
    }

    /**
     * 🆕 Delete private thread (nakes only - yang assigned)
     */
    public function deletePrivateThread(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);
        
        $thread = ForumThread::findOrFail($id);
        
        // Validasi: Hanya nakes yang di-assign yang bisa hapus
        if ($user->role === 'nakes') {
            if (!$thread->is_private) {
                return response()->json(['message' => 'Hanya bisa menghapus pertanyaan private'], 403);
            }
            if ($thread->assigned_nakes_id !== $user->id) {
                return response()->json(['message' => 'Anda tidak bisa menghapus pertanyaan ini'], 403);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Hapus semua replies terlebih dahulu
        $thread->replies()->delete();
        
        // Hapus thread
        $thread->delete();
        
        return response()->json(['message' => 'Pertanyaan berhasil dihapus']);
    }
}
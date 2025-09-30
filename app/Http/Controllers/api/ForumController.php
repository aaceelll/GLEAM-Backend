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
        $user = auth()->user();
        $type = $request->query('type', 'public'); // 'public' or 'private'

        $query = ForumThread::with(['user', 'category', 'assignedNakes'])
            ->withCount('replies');

        // Filter berdasarkan type
        if ($type === 'public') {
            $query->public();
        } else {
            $query->private();
            // Private: hanya milik user atau assigned ke nakes
            if ($user->role !== 'admin') {
                $query->where(function($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('assigned_nakes_id', $user->id);
                });
            }
        }

        // Filter kategori
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('content', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        $query->orderBy('is_pinned', 'desc')
              ->orderBy('last_activity_at', 'desc');

        $threads = $query->paginate(15);
        
        // Check if current user liked each thread
        foreach ($threads as $thread) {
            $thread->is_liked = ForumThreadLike::where('thread_id', $thread->id)
                ->where('user_id', $user->id)->exists();
        }

        return response()->json($threads);
    }

    // Get thread detail
    public function getThreadDetail($id)
    {
        $user = auth()->user();
        
        $thread = ForumThread::with(['user', 'category', 'assignedNakes', 'replies.user', 'replies.likes'])
            ->findOrFail($id);
        
        // Authorization check untuk private threads
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
        
        // Increment view
        $thread->increment('view_count');

        // Check likes
        $thread->is_liked = ForumThreadLike::where('thread_id', $id)
            ->where('user_id', $user->id)->exists();

        foreach ($thread->replies as $reply) {
            $reply->is_liked = ForumReplyLike::where('reply_id', $reply->id)
                ->where('user_id', $user->id)->exists();
        }

        return response()->json($thread);
    }

    // Create thread (public or private)
    public function createThread(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:forum_categories,id',
            'title' => 'required|max:255',
            'content' => 'required',
            'is_private' => 'boolean',
        ]);

        $user = auth()->user();

        $thread = ForumThread::create([
            'user_id' => $user->id,
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'is_private' => $validated['is_private'] ?? false,
            'last_activity_at' => now(),
        ]);

        ForumCategory::find($validated['category_id'])->increment('thread_count');

        return response()->json([
            'message' => $thread->is_private ? 'Pertanyaan private berhasil dikirim' : 'Thread berhasil dibuat',
            'thread' => $thread->load('user', 'category')
        ], 201);
    }

    // Reply to thread
    public function replyThread(Request $request, $id)
    {
        $validated = $request->validate(['content' => 'required']);
        $user = auth()->user();

        $thread = ForumThread::findOrFail($id);

        // Check authorization untuk private
        if ($thread->is_private) {
            if ($user->role === 'admin') {
                return response()->json(['message' => 'Admin tidak bisa reply ke pertanyaan private'], 403);
            }
            
            if ($user->role === 'user' && $thread->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            if ($user->role === 'nakes' && $thread->assigned_nakes_id !== $user->id) {
                return response()->json(['message' => 'Anda belum mengambil pertanyaan ini'], 403);
            }
        }

        if ($thread->is_locked) {
            return response()->json(['message' => 'Thread sudah dikunci'], 403);
        }

        $reply = ForumReply::create([
            'thread_id' => $id,
            'user_id' => $user->id,
            'content' => $validated['content'],
            'responder_role' => $user->role,
        ]);

        $thread->increment('reply_count');
        $thread->update(['last_activity_at' => now()]);

        return response()->json([
            'message' => 'Balasan berhasil dikirim',
            'reply' => $reply->load('user')
        ], 201);
    }

    // Like thread
    public function likeThread($id)
    {
        $thread = ForumThread::findOrFail($id);
        $userId = auth()->id();

        $like = ForumThreadLike::where('thread_id', $id)->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();
            $thread->decrement('like_count');
            $liked = false;
        } else {
            ForumThreadLike::create(['thread_id' => $id, 'user_id' => $userId]);
            $thread->increment('like_count');
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'like_count' => $thread->like_count]);
    }

    // Like reply
    public function likeReply($id)
    {
        $reply = ForumReply::findOrFail($id);
        $userId = auth()->id();

        $like = ForumReplyLike::where('reply_id', $id)->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();
            $reply->decrement('like_count');
            $liked = false;
        } else {
            ForumReplyLike::create(['reply_id' => $id, 'user_id' => $userId]);
            $reply->increment('like_count');
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'like_count' => $reply->like_count]);
    }

    // Delete thread
    public function deleteThread($id)
    {
        $thread = ForumThread::findOrFail($id);
        $user = auth()->user();

        if ($thread->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Admin hanya bisa delete public threads
        if ($user->role === 'admin' && $thread->is_private) {
            return response()->json(['message' => 'Admin tidak bisa menghapus pertanyaan private'], 403);
        }

        $thread->delete();
        return response()->json(['message' => 'Thread berhasil dihapus']);
    }

    // Delete reply
    public function deleteReply($id)
    {
        $reply = ForumReply::findOrFail($id);
        $user = auth()->user();

        if ($reply->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Admin hanya bisa delete di public threads
        if ($user->role === 'admin' && $reply->thread->is_private) {
            return response()->json(['message' => 'Admin tidak bisa menghapus balasan di pertanyaan private'], 403);
        }

        $reply->delete();
        $reply->thread->decrement('reply_count');
        
        return response()->json(['message' => 'Balasan berhasil dihapus']);
    }

    // ===== NAKES ONLY: Private Questions Management =====
    
    // Get pending private threads (belum diambil nakes)
    public function getPendingPrivateThreads()
    {
        $threads = ForumThread::private()
            ->with(['user', 'category'])
            ->whereNull('assigned_nakes_id')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($threads);
    }

    // Get nakes's assigned private threads
    public function getMyPrivateThreads()
    {
        $threads = ForumThread::private()
            ->with(['user', 'category', 'replies.user'])
            ->where('assigned_nakes_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($threads);
    }

    // Assign thread to self (Nakes only)
    public function assignToSelf($id)
    {
        $thread = ForumThread::findOrFail($id);

        if (!$thread->is_private) {
            return response()->json(['message' => 'Thread ini bukan pertanyaan private'], 400);
        }

        if ($thread->assigned_nakes_id) {
            return response()->json(['message' => 'Pertanyaan sudah diambil oleh nakes lain'], 400);
        }

        $thread->update(['assigned_nakes_id' => auth()->id()]);

        return response()->json(['message' => 'Pertanyaan berhasil diambil']);
    }

    // Close thread
    public function closeThread($id)
    {
        $thread = ForumThread::findOrFail($id);
        $user = auth()->user();

        // Authorization
        if ($thread->user_id !== $user->id && 
            $thread->assigned_nakes_id !== $user->id && 
            $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $thread->update(['is_locked' => true]);

        return response()->json(['message' => 'Thread ditutup']);
    }

    // ===== ADMIN ONLY: Manage Public Threads =====
    
    // Pin thread (Admin only, public only)
    public function pinThread($id)
    {
        $thread = ForumThread::findOrFail($id);
        
        if ($thread->is_private) {
            return response()->json(['message' => 'Tidak bisa pin pertanyaan private'], 403);
        }

        $thread->is_pinned = !$thread->is_pinned;
        $thread->save();

        return response()->json(['message' => 'Thread ' . ($thread->is_pinned ? 'dipin' : 'unpin')]);
    }

    // Lock thread (Admin only, public only)
    public function lockThread($id)
    {
        $thread = ForumThread::findOrFail($id);
        
        if ($thread->is_private) {
            return response()->json(['message' => 'Tidak bisa lock pertanyaan private'], 403);
        }

        $thread->is_locked = !$thread->is_locked;
        $thread->save();

        return response()->json(['message' => 'Thread ' . ($thread->is_locked ? 'dikunci' : 'dibuka')]);
    }

    // Force delete (Admin only, public only)
    public function forceDeleteThread($id)
    {
        $thread = ForumThread::findOrFail($id);
        
        if ($thread->is_private) {
            return response()->json(['message' => 'Tidak bisa force delete pertanyaan private'], 403);
        }

        $thread->delete();
        
        return response()->json(['message' => 'Thread berhasil dihapus']);
    }
}
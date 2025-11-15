<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumThread;

class ForumAdminController extends Controller
{

    // pin/unpin forum
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

    // lock/unlock forum
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

    // delete forum
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
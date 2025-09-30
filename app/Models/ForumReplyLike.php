<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumReplyLike extends Model
{
    protected $fillable = ['reply_id', 'user_id'];

    public function reply()
    {
        return $this->belongsTo(ForumReply::class, 'reply_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
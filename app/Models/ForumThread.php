<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumThread extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'title', 'content',
        'is_pinned', 'is_locked', 'is_private', 'assigned_nakes_id',
        'view_count', 'reply_count', 'like_count', 'last_activity_at'
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'is_private' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ForumCategory::class, 'category_id');
    }

    public function assignedNakes()
    {
        return $this->belongsTo(User::class, 'assigned_nakes_id');
    }

    public function replies()
    {
        return $this->hasMany(ForumReply::class, 'thread_id');
    }

    public function likes()
    {
        return $this->hasMany(ForumThreadLike::class, 'thread_id');
    }

    // Scope untuk public threads
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    // Scope untuk private threads
    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }
}
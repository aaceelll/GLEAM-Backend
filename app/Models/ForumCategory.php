<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'icon', 'color', 'description', 'thread_count'
    ];

    public function threads()
    {
        return $this->hasMany(ForumThread::class, 'category_id');
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Kategori Forum
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('💬');
            $table->string('color')->default('#10b981');
            $table->text('description')->nullable();
            $table->integer('thread_count')->default(0);
            $table->timestamps();
        });

        // Tabel Thread/Topik Forum
        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('forum_categories')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_private')->default(false);
            $table->foreignId('assigned_nakes_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('view_count')->default(0);
            $table->integer('reply_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            
            $table->index(['category_id', 'is_pinned', 'last_activity_at']);
            $table->index(['is_private', 'user_id']);
            $table->index('assigned_nakes_id');
        });

        // Tabel Balasan Forum
        Schema::create('forum_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->enum('responder_role', ['user', 'nakes', 'admin'])->default('user');
            $table->integer('like_count')->default(0);
            $table->timestamps();
        });

        // Tabel Likes Thread
        Schema::create('forum_thread_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['thread_id', 'user_id']);
        });

        // Tabel Likes Reply
        Schema::create('forum_reply_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reply_id')->constrained('forum_replies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['reply_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_reply_likes');
        Schema::dropIfExists('forum_thread_likes');
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_threads');
        Schema::dropIfExists('forum_categories');
    }
};
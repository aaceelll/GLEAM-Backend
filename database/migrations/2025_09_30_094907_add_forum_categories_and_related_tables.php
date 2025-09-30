<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Buat tabel categories dulu
        if (!Schema::hasTable('forum_categories')) {
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
        }

        // Tambah kolom di forum_threads jika belum ada
        if (Schema::hasTable('forum_threads') && !Schema::hasColumn('forum_threads', 'is_private')) {
            Schema::table('forum_threads', function (Blueprint $table) {
                $table->boolean('is_private')->default(false)->after('is_locked');
                $table->foreignId('assigned_nakes_id')->nullable()->constrained('users')->onDelete('set null')->after('is_private');
            });
        }

        // Buat tabel replies
        if (!Schema::hasTable('forum_replies')) {
            Schema::create('forum_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('content');
                $table->enum('responder_role', ['user', 'nakes', 'admin'])->default('user');
                $table->integer('like_count')->default(0);
                $table->timestamps();
            });
        }

        // Buat tabel thread likes
        if (!Schema::hasTable('forum_thread_likes')) {
            Schema::create('forum_thread_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('thread_id')->constrained('forum_threads')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['thread_id', 'user_id']);
            });
        }

        // Buat tabel reply likes
        if (!Schema::hasTable('forum_reply_likes')) {
            Schema::create('forum_reply_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reply_id')->constrained('forum_replies')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->unique(['reply_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_reply_likes');
        Schema::dropIfExists('forum_thread_likes');
        Schema::dropIfExists('forum_replies');
        
        if (Schema::hasTable('forum_threads')) {
            Schema::table('forum_threads', function (Blueprint $table) {
                if (Schema::hasColumn('forum_threads', 'assigned_nakes_id')) {
                    $table->dropForeign(['assigned_nakes_id']);
                    $table->dropColumn(['is_private', 'assigned_nakes_id']);
                }
            });
        }
        
        Schema::dropIfExists('forum_categories');
    }
};
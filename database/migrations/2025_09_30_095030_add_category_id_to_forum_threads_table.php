<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            // Tambah category_id jika belum ada
            if (!Schema::hasColumn('forum_threads', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('user_id')->constrained('forum_categories')->onDelete('cascade');
            }
            
            // Pastikan kolom lain juga ada
            if (!Schema::hasColumn('forum_threads', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false);
            }
            
            if (!Schema::hasColumn('forum_threads', 'is_locked')) {
                $table->boolean('is_locked')->default(false);
            }
            
            if (!Schema::hasColumn('forum_threads', 'view_count')) {
                $table->integer('view_count')->default(0);
            }
            
            if (!Schema::hasColumn('forum_threads', 'reply_count')) {
                $table->integer('reply_count')->default(0);
            }
            
            if (!Schema::hasColumn('forum_threads', 'like_count')) {
                $table->integer('like_count')->default(0);
            }
            
            if (!Schema::hasColumn('forum_threads', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            if (Schema::hasColumn('forum_threads', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
        });
    }
};
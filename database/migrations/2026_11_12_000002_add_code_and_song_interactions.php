<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('code_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_snippet_id')->constrained('code_snippets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['code_snippet_id', 'user_id']);
        });

        Schema::create('code_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('code_snippet_id')->constrained('code_snippets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['code_snippet_id', 'created_at']);
        });

        Schema::create('song_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('favorite_song_id')->constrained('favorite_songs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['favorite_song_id', 'user_id']);
        });

        Schema::create('song_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('favorite_song_id')->constrained('favorite_songs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['favorite_song_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_comments');
        Schema::dropIfExists('song_likes');
        Schema::dropIfExists('code_comments');
        Schema::dropIfExists('code_likes');
    }
};

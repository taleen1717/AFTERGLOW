<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
            $table->string('bio', 280)->nullable();
        });
        Schema::table('favorite_songs', function (Blueprint $table) {
            // Existing songs stay private until their owner explicitly shares them.
            $table->boolean('is_public')->default(false)->index();
        });
        Schema::create('quote_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['quote_id', 'user_id']);
        });
        Schema::create('board_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visual_board_id')->constrained('visual_boards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['visual_board_id', 'user_id']);
        });
        Schema::create('quote_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['quote_id', 'created_at']);
        });
        Schema::create('board_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visual_board_id')->constrained('visual_boards')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['visual_board_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_comments');
        Schema::dropIfExists('quote_comments');
        Schema::dropIfExists('board_likes');
        Schema::dropIfExists('quote_likes');
        Schema::table('favorite_songs', fn (Blueprint $table) => $table->dropColumn('is_public'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['avatar_path', 'bio']));
    }
};

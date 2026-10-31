<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visual_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 90);
            $table->text('description')->nullable();
            $table->string('mood', 30);
            $table->string('image_path', 255);
            $table->timestamps();
            $table->index(['mood', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visual_boards');
    }
};

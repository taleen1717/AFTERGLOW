<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->string('title', 120);
            $table->string('artist', 120);
            $table->string('url', 500);
            $table->string('mood', 40);
            $table->boolean('is_on_repeat')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_on_repeat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_songs');
    }
};

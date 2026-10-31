<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('favorite_songs', 'artwork_url')) {
            Schema::table('favorite_songs', fn (Blueprint $t) => $t->string('artwork_url', 600)->nullable());
        }
        if (!Schema::hasColumn('favorite_songs', 'platform')) {
            Schema::table('favorite_songs', fn (Blueprint $t) => $t->string('platform', 20)->nullable());
        }
    }
    public function down(): void {
        // Deliberately preserve user data; no destructive rollback in this add-on.
    }
};

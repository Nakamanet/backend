<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('Saved_Posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('Users')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained('Posts')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'post_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Saved_Posts');
    }
};
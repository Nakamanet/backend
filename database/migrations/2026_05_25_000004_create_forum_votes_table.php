<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Forum_Votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('Users')->cascadeOnDelete();
            $table->enum('target_type', ['topic', 'reply']);
            $table->unsignedBigInteger('target_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Forum_Votes');
    }
};

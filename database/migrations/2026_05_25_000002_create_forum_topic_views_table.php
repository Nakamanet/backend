<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Forum_Topic_Views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('Forum_Topics')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('Users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['topic_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Forum_Topic_Views');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Forum_User_Pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('Users')->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained('Forum_Topics')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Forum_User_Pins');
    }
};

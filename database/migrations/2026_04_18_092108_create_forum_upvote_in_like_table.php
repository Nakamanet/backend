<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('Likes', function (Blueprint $table) {
            $table->unsignedBigInteger('forum_topic_id')->nullable();
            $table->unsignedBigInteger('forum_reply_id')->nullable();

            $table->foreign('forum_topic_id')->references('id')->on('Forum_Topics')->onDelete('cascade');
            $table->foreign('forum_reply_id')->references('id')->on('Forum_Replies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_upvote_in_like');
    }
};

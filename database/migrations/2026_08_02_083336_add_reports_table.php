<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('Users')->cascadeOnDelete();
            $table->string('reportable_type'); // e.g. 'post', 'comment', 'message'
            $table->unsignedBigInteger('reportable_id'); // Posts.id, Comments.id, or Mongo _id as string later
            $table->string('reason')->nullable(); // e.g. 'spam', 'harassment', 'nsfw', 'other'
            $table->text('details')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'dismissed', 'action_taken'])->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('Users')->nullOnDelete();

            $table->unique(['reporter_id', 'reportable_type', 'reportable_id']); // one report per user per item
            $table->index(['reportable_type', 'reportable_id']); // fast lookup/count per item
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Reports');
    }
};

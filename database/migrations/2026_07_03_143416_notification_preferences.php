<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('Users')
                ->cascadeOnDelete();

            $table->boolean('comment')->default(true);
            $table->boolean('friends_requests')->default(true);
            $table->boolean('mentions')->default(true);
            $table->boolean('new_sorties')->default(true);
            $table->boolean('messages')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};

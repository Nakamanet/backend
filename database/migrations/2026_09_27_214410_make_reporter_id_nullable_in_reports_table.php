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
        Schema::table('Reports', function (Blueprint $table) {
            $table->dropForeign(['reporter_id']);
            $table->unsignedBigInteger('reporter_id')->nullable()->change();
            $table->foreign('reporter_id')->references('id')->on('Users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Reports', function (Blueprint $table) {
            $table->dropForeign(['reporter_id']);
            $table->unsignedBigInteger('reporter_id')->nullable(false)->change();
            $table->foreign('reporter_id')->references('id')->on('Users')->cascadeOnDelete();
        });
    }
};

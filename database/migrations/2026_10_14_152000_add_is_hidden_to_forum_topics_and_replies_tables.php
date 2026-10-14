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
        Schema::table('Forum_Topics', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false);
        });

        Schema::table('Forum_Replies', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Forum_Topics', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });

        Schema::table('Forum_Replies', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
};

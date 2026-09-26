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
        Schema::table('Posts', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false);
        });

        Schema::table('Comments', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('Posts', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });

        Schema::table('Comments', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
};

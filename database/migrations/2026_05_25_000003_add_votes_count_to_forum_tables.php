<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Forum_Topics', function (Blueprint $table) {
            $table->unsignedBigInteger('votes_count')->default(0)->after('views_count');
        });

        Schema::table('Forum_Replies', function (Blueprint $table) {
            $table->unsignedBigInteger('votes_count')->default(0)->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('Forum_Topics', function (Blueprint $table) {
            $table->dropColumn('votes_count');
        });

        Schema::table('Forum_Replies', function (Blueprint $table) {
            $table->dropColumn('votes_count');
        });
    }
};

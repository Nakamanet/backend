<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Users', function (Blueprint $table) {
            $table->string('handle', 50)->nullable()->after('username');
        });

        // handle = whatever username already is, for every existing user
        DB::statement('UPDATE "Users" SET handle = username');

        Schema::table('Users', function (Blueprint $table) {
            $table->string('handle', 50)->nullable(false)->change();
            $table->unique('handle');
        });
    }

    public function down(): void
    {
        Schema::table('Users', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropColumn('handle');
        });
    }
};

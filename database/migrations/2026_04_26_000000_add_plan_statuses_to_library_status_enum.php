<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TYPE library_status ADD VALUE IF NOT EXISTS 'plan_to_watch'");
        DB::statement("ALTER TYPE library_status ADD VALUE IF NOT EXISTS 'plan_to_read'");
    }

    public function down(): void
    {
        // PostgreSQL does not support removing enum values directly.
        // A full enum recreation would be required.
    }
};

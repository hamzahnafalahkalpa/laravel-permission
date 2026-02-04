<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Changes visibility column from boolean to smallint for PgBouncer compatibility.
     */
    public function up(): void
    {
        // PostgreSQL: ALTER COLUMN with type cast
        DB::statement('ALTER TABLE permissions ALTER COLUMN visibility TYPE smallint USING visibility::integer');
        DB::statement('ALTER TABLE permissions ALTER COLUMN visibility SET DEFAULT 1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to boolean
        DB::statement('ALTER TABLE permissions ALTER COLUMN visibility TYPE boolean USING visibility::boolean');
        DB::statement('ALTER TABLE permissions ALTER COLUMN visibility SET DEFAULT true');
    }
};

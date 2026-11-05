<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tests run on sqlite, which has no EXTRACT(EPOCH ...).
        $seconds = DB::getDriverName() === 'pgsql'
            ? 'EXTRACT(EPOCH FROM (end_time - start_time))::bigint'
            : "CAST(strftime('%s', end_time) AS INTEGER) - CAST(strftime('%s', start_time) AS INTEGER)";

        DB::statement("
            CREATE VIEW work_view AS
            SELECT 
                user_id,
                DATE(start_time) as date,
                shift_categories.owner_id as category_owner_id,
                SUM({$seconds}) as total_shift_seconds
            FROM shifts
            JOIN shift_categories ON shift_categories.id = shifts.shift_category_id
            WHERE shift_categories.owner_id IS NOT NULL
            GROUP BY user_id, DATE(start_time), shift_categories.owner_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS work_view');
    }
};

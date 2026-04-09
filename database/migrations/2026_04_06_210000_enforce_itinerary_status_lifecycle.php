<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('itineraries') || ! Schema::hasColumn('itineraries', 'status')) {
            return;
        }

        DB::statement("ALTER TABLE itineraries MODIFY COLUMN status ENUM('draft','processed','pending','approved','rejected','final') NOT NULL DEFAULT 'pending'");
        DB::table('itineraries')->where('status', 'draft')->update(['status' => 'pending']);
        DB::table('itineraries')->where('status', 'approved')->update(['status' => 'final']);
        DB::table('itineraries')->where('status', 'rejected')->update(['status' => 'processed']);
        DB::statement("UPDATE itineraries SET status = 'pending' WHERE status <> 'final'");
        DB::statement("
            UPDATE itineraries i
            INNER JOIN quotations q ON q.itinerary_id = i.id AND q.deleted_at IS NULL
            SET i.status = CASE
                WHEN q.status = 'approved' THEN 'final'
                ELSE 'processed'
            END
            WHERE i.status <> 'final'
        ");
        DB::statement("ALTER TABLE itineraries MODIFY COLUMN status ENUM('pending','processed','final') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('itineraries') || ! Schema::hasColumn('itineraries', 'status')) {
            return;
        }

        DB::statement("ALTER TABLE itineraries MODIFY COLUMN status ENUM('draft','processed','pending','approved','rejected','final') NOT NULL DEFAULT 'draft'");
        DB::table('itineraries')->where('status', 'pending')->update(['status' => 'draft']);
    }
};

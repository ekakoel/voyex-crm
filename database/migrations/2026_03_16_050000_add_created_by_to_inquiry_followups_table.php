<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiry_followups', function (Blueprint $table) {
            if (! Schema::hasColumn('inquiry_followups', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('note')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        DB::statement(
            "UPDATE inquiry_followups
            INNER JOIN inquiries ON inquiries.id = inquiry_followups.inquiry_id
            SET inquiry_followups.created_by = inquiries.assigned_to
            WHERE inquiry_followups.created_by IS NULL"
        );
    }

    public function down(): void
    {
        Schema::table('inquiry_followups', function (Blueprint $table) {
            if (Schema::hasColumn('inquiry_followups', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};

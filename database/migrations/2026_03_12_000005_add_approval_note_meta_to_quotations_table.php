<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'approval_note_by')) {
                $table->foreignId('approval_note_by')->nullable()->constrained('users')->nullOnDelete()->after('approval_note');
            }
            if (! Schema::hasColumn('quotations', 'approval_note_at')) {
                $table->timestamp('approval_note_at')->nullable()->after('approval_note_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'approval_note_at')) {
                $table->dropColumn('approval_note_at');
            }
            if (Schema::hasColumn('quotations', 'approval_note_by')) {
                $table->dropForeign(['approval_note_by']);
                $table->dropColumn('approval_note_by');
            }
        });
    }
};

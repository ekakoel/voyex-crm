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
        if (Schema::hasColumn('quotations', 'approval_note')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->text('approval_note')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('quotations', 'approval_note')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('approval_note');
        });
    }
};

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
        Schema::table('inquiry_followups', function (Blueprint $table) {
            if (! Schema::hasColumn('inquiry_followups', 'last_reminded_at')) {
                $table->dateTime('last_reminded_at')->nullable()->after('done_reason');
                $table->index('last_reminded_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiry_followups', function (Blueprint $table) {
            if (Schema::hasColumn('inquiry_followups', 'last_reminded_at')) {
                $table->dropIndex(['last_reminded_at']);
                $table->dropColumn('last_reminded_at');
            }
        });
    }
};


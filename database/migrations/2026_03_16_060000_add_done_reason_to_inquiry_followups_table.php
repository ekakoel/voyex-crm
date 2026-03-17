<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiry_followups', function (Blueprint $table) {
            if (! Schema::hasColumn('inquiry_followups', 'done_reason')) {
                $table->text('done_reason')->nullable()->after('done_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inquiry_followups', function (Blueprint $table) {
            if (Schema::hasColumn('inquiry_followups', 'done_reason')) {
                $table->dropColumn('done_reason');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotations') && Schema::hasColumn('quotations', 'approval_status')) {
            \Illuminate\Support\Facades\DB::table('quotations')
                ->where('approval_status', 'approved')
                ->update(['status' => 'approved']);
            \Illuminate\Support\Facades\DB::table('quotations')
                ->where('approval_status', 'rejected')
                ->update(['status' => 'rejected']);
            \Illuminate\Support\Facades\DB::table('quotations')
                ->where('approval_status', 'submitted')
                ->update(['status' => 'pending']);

            Schema::table('quotations', function (Blueprint $table) {
                $table->dropColumn('approval_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quotations') && ! Schema::hasColumn('quotations', 'approval_status')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->enum('approval_status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            });
        }
    }
};

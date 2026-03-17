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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            $table->string('status')->default('draft')->change();
        });

        // To ensure existing data is consistent, update old statuses
        // We assume 'issued' is the main active status, and 'paid' is final.
        DB::table('invoices')->where('status', 'issued')->update(['status' => 'sent']);
        DB::table('invoices')->where('status', 'paid')->update(['status' => 'paid']);
        DB::table('invoices')->where('status', 'void')->update(['status' => 'void']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
            // Reverting status column change is complex, 
            // but for downgrade we can try to map them back.
            // This assumes doctrine/dbal is installed.
            $table->enum('status', ['issued', 'paid', 'void'])->default('issued')->change();
        });
    }
};

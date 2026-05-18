<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicateRows = DB::table('bookings')
            ->select('quotation_id', DB::raw('COUNT(*) as total'))
            ->groupBy('quotation_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateRows->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot add unique index bookings.quotation_id because duplicate quotation_id rows still exist.'
            );
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique('quotation_id', 'bookings_quotation_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_quotation_id_unique');
        });
    }
};


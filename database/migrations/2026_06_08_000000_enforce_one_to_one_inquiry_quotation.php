<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotations') || ! Schema::hasColumn('quotations', 'inquiry_id')) {
            return;
        }

        $duplicateInquiryId = DB::table('quotations')
            ->whereNotNull('inquiry_id')
            ->groupBy('inquiry_id')
            ->havingRaw('COUNT(*) > 1')
            ->value('inquiry_id');

        if ($duplicateInquiryId) {
            throw new RuntimeException(
                "Cannot enforce one-to-one inquiry quotation relation. Inquiry ID {$duplicateInquiryId} has multiple quotations. ".
                'Please resolve duplicate quotation links before re-running migration.'
            );
        }

        Schema::table('quotations', function (Blueprint $table): void {
            $table->unique('inquiry_id', 'quotations_inquiry_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotations') || ! Schema::hasColumn('quotations', 'inquiry_id')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropUnique('quotations_inquiry_id_unique');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_item_vouchers')) {
            return;
        }

        // Convert enum to varchar first to avoid MySQL data truncation
        // when writing new lifecycle status values (e.g. generated, reissued).
        DB::statement("ALTER TABLE booking_item_vouchers MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'draft'");

        Schema::table('booking_item_vouchers', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_item_vouchers', 'revision_number')) {
                $table->unsignedInteger('revision_number')
                    ->default(1)
                    ->after('voucher_number');
            }
        });

        DB::table('booking_item_vouchers')
            ->where('status', 'issued')
            ->update(['status' => 'generated']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_item_vouchers')) {
            return;
        }

        DB::table('booking_item_vouchers')
            ->where('status', 'generated')
            ->update(['status' => 'issued']);

        Schema::table('booking_item_vouchers', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_item_vouchers', 'revision_number')) {
                $table->dropColumn('revision_number');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'invoice_type')) {
                    $table->string('invoice_type', 50)->default('full_payment')->after('booking_id');
                }
                if (! Schema::hasColumn('invoices', 'subtotal')) {
                    $table->decimal('subtotal', 15, 2)->default(0)->after('due_date');
                }
                if (! Schema::hasColumn('invoices', 'discount_amount')) {
                    $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');
                }
                if (! Schema::hasColumn('invoices', 'tax_amount')) {
                    $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
                }
                if (! Schema::hasColumn('invoices', 'paid_amount')) {
                    $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
                }
                if (! Schema::hasColumn('invoices', 'balance_amount')) {
                    $table->decimal('balance_amount', 15, 2)->default(0)->after('paid_amount');
                }
            });

            // Drop old one-to-one unique to allow multiple invoices per booking.
            try {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->dropUnique('invoices_booking_id_unique');
                });
            } catch (\Throwable $e) {
                // keep backward-compatible if index name differs or already dropped
            }

            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['booking_id', 'invoice_type'], 'invoices_booking_invoice_type_index');
            });

            DB::table('invoices')
                ->whereNull('invoice_type')
                ->update(['invoice_type' => 'full_payment']);

            DB::table('invoices')
                ->update([
                    'subtotal' => DB::raw('COALESCE(subtotal, total_amount, 0)'),
                    'discount_amount' => DB::raw('COALESCE(discount_amount, 0)'),
                    'tax_amount' => DB::raw('COALESCE(tax_amount, 0)'),
                    'paid_amount' => DB::raw('COALESCE(paid_amount, 0)'),
                    'balance_amount' => DB::raw('GREATEST(COALESCE(total_amount, 0) - COALESCE(paid_amount, 0), 0)'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        try {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex('invoices_booking_invoice_type_index');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('invoices', function (Blueprint $table) {
            foreach (['invoice_type', 'subtotal', 'discount_amount', 'tax_amount', 'balance_amount'] as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // restore unique booking_id for old behavior if downgrade is needed
        try {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unique('booking_id');
            });
        } catch (\Throwable $e) {
        }
    }
};


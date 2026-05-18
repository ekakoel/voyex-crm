<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'notes')) {
                    $table->dropColumn('notes');
                }
            });
        }

        if (Schema::hasTable('booking_items')) {
            Schema::table('booking_items', function (Blueprint $table) {
                if (Schema::hasColumn('booking_items', 'notes')) {
                    $table->dropColumn('notes');
                }
                if (Schema::hasColumn('booking_items', 'cancellation_notes')) {
                    $table->dropColumn('cancellation_notes');
                }
            });
        }

        if (Schema::hasTable('booking_item_booking_logs')) {
            Schema::table('booking_item_booking_logs', function (Blueprint $table) {
                if (Schema::hasColumn('booking_item_booking_logs', 'notes')) {
                    $table->dropColumn('notes');
                }
            });
        }

        if (Schema::hasTable('booking_item_vouchers')) {
            Schema::table('booking_item_vouchers', function (Blueprint $table) {
                if (Schema::hasColumn('booking_item_vouchers', 'notes')) {
                    $table->dropColumn('notes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('bookings', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        if (Schema::hasTable('booking_items')) {
            Schema::table('booking_items', function (Blueprint $table) {
                if (! Schema::hasColumn('booking_items', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (! Schema::hasColumn('booking_items', 'cancellation_notes')) {
                    $table->text('cancellation_notes')->nullable();
                }
            });
        }

        if (Schema::hasTable('booking_item_booking_logs')) {
            Schema::table('booking_item_booking_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('booking_item_booking_logs', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        if (Schema::hasTable('booking_item_vouchers')) {
            Schema::table('booking_item_vouchers', function (Blueprint $table) {
                if (! Schema::hasColumn('booking_item_vouchers', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }
    }
};


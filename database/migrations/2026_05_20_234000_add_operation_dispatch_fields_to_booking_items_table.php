<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_items')) {
            return;
        }

        Schema::table('booking_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_items', 'vendor_confirmation_status')) {
                $table->string('vendor_confirmation_status', 30)->default('pending')->after('status');
            }
            if (! Schema::hasColumn('booking_items', 'vendor_confirmed_at')) {
                $table->timestamp('vendor_confirmed_at')->nullable()->after('vendor_confirmation_status');
            }
            if (! Schema::hasColumn('booking_items', 'vendor_confirmed_by')) {
                $table->foreignId('vendor_confirmed_by')->nullable()->after('vendor_confirmed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('booking_items', 'assigned_driver_name')) {
                $table->string('assigned_driver_name')->nullable()->after('vendor_confirmed_by');
            }
            if (! Schema::hasColumn('booking_items', 'assigned_driver_phone')) {
                $table->string('assigned_driver_phone')->nullable()->after('assigned_driver_name');
            }
            if (! Schema::hasColumn('booking_items', 'assigned_guide_name')) {
                $table->string('assigned_guide_name')->nullable()->after('assigned_driver_phone');
            }
            if (! Schema::hasColumn('booking_items', 'assigned_guide_phone')) {
                $table->string('assigned_guide_phone')->nullable()->after('assigned_guide_name');
            }
            if (! Schema::hasColumn('booking_items', 'operation_notes')) {
                $table->text('operation_notes')->nullable()->after('assigned_guide_phone');
            }
            if (! Schema::hasColumn('booking_items', 'dispatch_status')) {
                $table->string('dispatch_status', 30)->default('pending')->after('operation_notes');
            }
            if (! Schema::hasColumn('booking_items', 'issue_note')) {
                $table->text('issue_note')->nullable()->after('dispatch_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_items')) {
            return;
        }

        Schema::table('booking_items', function (Blueprint $table): void {
            foreach ([
                'vendor_confirmed_by',
                'vendor_confirmed_at',
                'vendor_confirmation_status',
                'assigned_driver_name',
                'assigned_driver_phone',
                'assigned_guide_name',
                'assigned_guide_phone',
                'operation_notes',
                'dispatch_status',
                'issue_note',
            ] as $column) {
                if (Schema::hasColumn('booking_items', $column)) {
                    if ($column === 'vendor_confirmed_by') {
                        try {
                            $table->dropConstrainedForeignId('vendor_confirmed_by');
                        } catch (\Throwable $e) {
                            $table->dropColumn('vendor_confirmed_by');
                        }
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};


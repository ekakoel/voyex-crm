<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Convert status columns from ENUM to VARCHAR first
        |--------------------------------------------------------------------------
        | This prevents MySQL "Data truncated" errors when updating old status
        | values into new business lifecycle statuses.
        */

        DB::statement("ALTER TABLE inquiries MODIFY status VARCHAR(50) NOT NULL DEFAULT 'new_request'");
        DB::statement("ALTER TABLE itineraries MODIFY status VARCHAR(50) NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE quotations MODIFY status VARCHAR(50) NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE bookings MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending_confirmation'");
        DB::statement("ALTER TABLE invoices MODIFY status VARCHAR(50) NOT NULL DEFAULT 'draft'");

        /*
        |--------------------------------------------------------------------------
        | 2. Map old inquiry statuses to new lifecycle statuses
        |--------------------------------------------------------------------------
        */

        DB::table('inquiries')
            ->whereIn('status', ['new', 'draft'])
            ->update(['status' => 'new_request']);

        DB::table('inquiries')
            ->whereIn('status', ['follow_up', 'processed', 'pending'])
            ->update(['status' => 'contacted']);

        DB::table('inquiries')
            ->whereIn('status', ['quoted'])
            ->update(['status' => 'quotation_sent']);

        DB::table('inquiries')
            ->whereIn('status', ['approved'])
            ->update(['status' => 'accepted']);

        DB::table('inquiries')
            ->whereIn('status', ['converted'])
            ->update(['status' => 'converted_to_booking']);

        DB::table('inquiries')
            ->whereIn('status', ['rejected'])
            ->update(['status' => 'lost']);

        DB::table('inquiries')
            ->whereIn('status', ['closed', 'final'])
            ->update(['status' => 'converted_to_booking']);

        /*
        |--------------------------------------------------------------------------
        | 3. Map itinerary statuses
        |--------------------------------------------------------------------------
        */

        DB::table('itineraries')
            ->whereIn('status', ['pending'])
            ->update(['status' => 'draft']);

        DB::table('itineraries')
            ->whereIn('status', ['processed'])
            ->update(['status' => 'approved']);

        DB::table('itineraries')
            ->whereIn('status', ['final'])
            ->update(['status' => 'confirmed']);

        /*
        |--------------------------------------------------------------------------
        | 4. Map quotation statuses
        |--------------------------------------------------------------------------
        */

        DB::table('quotations')
            ->whereIn('status', ['draft'])
            ->update(['status' => 'draft']);

        DB::table('quotations')
            ->whereIn('status', ['pending'])
            ->update(['status' => 'pending_validation']);

        DB::table('quotations')
            ->whereIn('status', ['processed'])
            ->update(['status' => 'sent']);

        DB::table('quotations')
            ->whereIn('status', ['approved', 'final'])
            ->update(['status' => 'accepted']);

        DB::table('quotations')
            ->whereIn('status', ['rejected'])
            ->update(['status' => 'rejected']);

        /*
        |--------------------------------------------------------------------------
        | 5. Map booking statuses
        |--------------------------------------------------------------------------
        */

        DB::table('bookings')
            ->whereIn('status', ['draft', 'pending'])
            ->update(['status' => 'pending_confirmation']);

        DB::table('bookings')
            ->whereIn('status', ['processed', 'approved'])
            ->update(['status' => 'confirmed']);

        DB::table('bookings')
            ->whereIn('status', ['final'])
            ->update(['status' => 'closed']);

        DB::table('bookings')
            ->whereIn('status', ['rejected'])
            ->update(['status' => 'cancelled']);

        /*
        |--------------------------------------------------------------------------
        | 6. Map invoice statuses
        |--------------------------------------------------------------------------
        */

        DB::table('invoices')
            ->whereIn('status', ['draft'])
            ->update(['status' => 'draft']);

        DB::table('invoices')
            ->whereIn('status', ['pending', 'processed'])
            ->update(['status' => 'issued']);

        DB::table('invoices')
            ->whereIn('status', ['approved', 'final'])
            ->update(['status' => 'paid']);

        DB::table('invoices')
            ->whereIn('status', ['rejected'])
            ->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Safe rollback mapping
        |--------------------------------------------------------------------------
        | Keep rollback simple and compatible with old generic statuses.
        */

        DB::table('inquiries')
            ->whereIn('status', [
                'new_request',
                'need_customer_data',
                'registered',
                'assigned',
                'contacted',
                'waiting_customer',
                'qualified',
                'itinerary_in_progress',
                'quotation_in_progress',
                'quotation_sent',
                'under_negotiation',
            ])
            ->update(['status' => 'pending']);

        DB::table('inquiries')
            ->whereIn('status', [
                'accepted',
                'converted_to_booking',
            ])
            ->update(['status' => 'final']);

        DB::table('inquiries')
            ->whereIn('status', [
                'lost',
                'cancelled',
                'expired',
                'unqualified',
            ])
            ->update(['status' => 'rejected']);

        DB::statement("ALTER TABLE inquiries MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE itineraries MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE quotations MODIFY status VARCHAR(50) NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE bookings MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE invoices MODIFY status VARCHAR(50) NOT NULL DEFAULT 'draft'");
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inquiries') && Schema::hasColumn('inquiries', 'status')) {
            DB::statement("ALTER TABLE inquiries MODIFY COLUMN status ENUM('new','follow_up','quoted','converted','closed','draft','processed','pending','approved','rejected','final') NOT NULL DEFAULT 'draft'");
            DB::table('inquiries')->where('status', 'new')->update(['status' => 'draft']);
            DB::table('inquiries')->where('status', 'follow_up')->update(['status' => 'processed']);
            DB::table('inquiries')->where('status', 'quoted')->update(['status' => 'pending']);
            DB::table('inquiries')->where('status', 'converted')->update(['status' => 'approved']);
            DB::table('inquiries')->where('status', 'closed')->update(['status' => 'final']);

            DB::statement("ALTER TABLE inquiries MODIFY COLUMN status ENUM('draft','processed','pending','approved','rejected','final') NOT NULL DEFAULT 'draft'");
        }

        if (Schema::hasTable('quotations') && Schema::hasColumn('quotations', 'status')) {
            DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('draft','pending','sent','approved','rejected','processed','final') NOT NULL DEFAULT 'draft'");
            DB::table('quotations')->where('status', 'draft')->update(['status' => 'draft']);
            DB::table('quotations')->where('status', 'pending')->update(['status' => 'pending']);
            DB::table('quotations')->where('status', 'sent')->update(['status' => 'processed']);
            DB::table('quotations')->where('status', 'approved')->update(['status' => 'approved']);
            DB::table('quotations')->where('status', 'rejected')->update(['status' => 'rejected']);

            DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('draft','processed','pending','approved','rejected','final') NOT NULL DEFAULT 'draft'");
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'status')) {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('confirmed','completed','cancelled','draft','processed','pending','approved','rejected','final') NOT NULL DEFAULT 'draft'");
            DB::table('bookings')->where('status', 'confirmed')->update(['status' => 'processed']);
            DB::table('bookings')->where('status', 'completed')->update(['status' => 'final']);
            DB::table('bookings')->where('status', 'cancelled')->update(['status' => 'rejected']);

            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('draft','processed','pending','approved','rejected','final') NOT NULL DEFAULT 'draft'");
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'status')) {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('issued','paid','void','draft','processed','pending','approved','rejected','final') NOT NULL DEFAULT 'draft'");
            DB::table('invoices')->where('status', 'issued')->update(['status' => 'pending']);
            DB::table('invoices')->where('status', 'paid')->update(['status' => 'final']);
            DB::table('invoices')->where('status', 'void')->update(['status' => 'rejected']);

            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','processed','pending','approved','rejected','final') NOT NULL DEFAULT 'draft'");
        }

        if (Schema::hasTable('itineraries') && ! Schema::hasColumn('itineraries', 'status')) {
            Schema::table('itineraries', function (Blueprint $table) {
                $table->enum('status', ['draft', 'processed', 'pending', 'approved', 'rejected', 'final'])
                    ->default('draft')
                    ->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inquiries') && Schema::hasColumn('inquiries', 'status')) {
            DB::table('inquiries')->where('status', 'draft')->update(['status' => 'new']);
            DB::table('inquiries')->where('status', 'processed')->update(['status' => 'follow_up']);
            DB::table('inquiries')->where('status', 'pending')->update(['status' => 'quoted']);
            DB::table('inquiries')->where('status', 'approved')->update(['status' => 'converted']);
            DB::table('inquiries')->where('status', 'rejected')->update(['status' => 'closed']);
            DB::table('inquiries')->where('status', 'final')->update(['status' => 'closed']);

            DB::statement("ALTER TABLE inquiries MODIFY COLUMN status ENUM('new','follow_up','quoted','converted','closed') NOT NULL DEFAULT 'new'");
        }

        if (Schema::hasTable('quotations') && Schema::hasColumn('quotations', 'status')) {
            DB::table('quotations')->where('status', 'draft')->update(['status' => 'draft']);
            DB::table('quotations')->where('status', 'processed')->update(['status' => 'sent']);
            DB::table('quotations')->where('status', 'pending')->update(['status' => 'pending']);
            DB::table('quotations')->where('status', 'approved')->update(['status' => 'approved']);
            DB::table('quotations')->where('status', 'rejected')->update(['status' => 'rejected']);
            DB::table('quotations')->where('status', 'final')->update(['status' => 'approved']);

            DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('draft','pending','sent','approved','rejected') NOT NULL DEFAULT 'draft'");
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'status')) {
            DB::table('bookings')->whereIn('status', ['draft', 'processed', 'pending', 'approved'])->update(['status' => 'confirmed']);
            DB::table('bookings')->where('status', 'final')->update(['status' => 'completed']);
            DB::table('bookings')->where('status', 'rejected')->update(['status' => 'cancelled']);

            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('confirmed','completed','cancelled') NOT NULL DEFAULT 'confirmed'");
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'status')) {
            DB::table('invoices')->whereIn('status', ['draft', 'processed', 'pending', 'approved'])->update(['status' => 'issued']);
            DB::table('invoices')->where('status', 'final')->update(['status' => 'paid']);
            DB::table('invoices')->where('status', 'rejected')->update(['status' => 'void']);

            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('issued','paid','void') NOT NULL DEFAULT 'issued'");
        }

        if (Schema::hasTable('itineraries') && Schema::hasColumn('itineraries', 'status')) {
            Schema::table('itineraries', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};

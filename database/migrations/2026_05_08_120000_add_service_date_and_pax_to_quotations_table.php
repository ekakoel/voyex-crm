<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            if (!Schema::hasColumn('quotations', 'service_date')) {
                $table->date('service_date')->nullable()->after('validity_date');
            }
            if (!Schema::hasColumn('quotations', 'pax_adult')) {
                $table->unsignedInteger('pax_adult')->default(0)->after('service_date');
            }
            if (!Schema::hasColumn('quotations', 'pax_child')) {
                $table->unsignedInteger('pax_child')->default(0)->after('pax_adult');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $dropColumns = [];
            if (Schema::hasColumn('quotations', 'pax_child')) {
                $dropColumns[] = 'pax_child';
            }
            if (Schema::hasColumn('quotations', 'pax_adult')) {
                $dropColumns[] = 'pax_adult';
            }
            if (Schema::hasColumn('quotations', 'service_date')) {
                $dropColumns[] = 'service_date';
            }
            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};


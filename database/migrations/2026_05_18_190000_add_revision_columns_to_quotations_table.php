<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotations', 'revision_of_id')) {
                $table->foreignId('revision_of_id')
                    ->nullable()
                    ->after('itinerary_id')
                    ->constrained('quotations')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('quotations', 'revision_number')) {
                $table->unsignedInteger('revision_number')
                    ->default(1)
                    ->after('revision_of_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            if (Schema::hasColumn('quotations', 'revision_of_id')) {
                $table->dropForeign(['revision_of_id']);
                $table->dropColumn('revision_of_id');
            }
            if (Schema::hasColumn('quotations', 'revision_number')) {
                $table->dropColumn('revision_number');
            }
        });
    }
};


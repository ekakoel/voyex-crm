<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotations', 'is_current_revision')) {
                $table->boolean('is_current_revision')
                    ->default(true)
                    ->after('revision_number');
            }

            if (! Schema::hasColumn('quotations', 'revision_reason')) {
                $table->text('revision_reason')
                    ->nullable()
                    ->after('is_current_revision');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            if (Schema::hasColumn('quotations', 'revision_reason')) {
                $table->dropColumn('revision_reason');
            }

            if (Schema::hasColumn('quotations', 'is_current_revision')) {
                $table->dropColumn('is_current_revision');
            }
        });
    }
};


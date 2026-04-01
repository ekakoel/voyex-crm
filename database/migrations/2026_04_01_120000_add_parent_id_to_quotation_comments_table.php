<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotation_comments')) {
            return;
        }

        Schema::table('quotation_comments', function (Blueprint $table) {
            if (! Schema::hasColumn('quotation_comments', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('quotation_comments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotation_comments')) {
            return;
        }

        Schema::table('quotation_comments', function (Blueprint $table) {
            if (Schema::hasColumn('quotation_comments', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};


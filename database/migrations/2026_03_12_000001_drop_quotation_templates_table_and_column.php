<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotations') && Schema::hasColumn('quotations', 'template_id')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropForeign(['template_id']);
                $table->dropColumn('template_id');
            });
        }

        Schema::dropIfExists('quotation_templates');
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotation_templates')) {
            Schema::create('quotation_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->longText('body_html');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('quotations') && ! Schema::hasColumn('quotations', 'template_id')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->foreignId('template_id')
                    ->nullable()
                    ->constrained('quotation_templates')
                    ->nullOnDelete();
            });
        }
    }
};

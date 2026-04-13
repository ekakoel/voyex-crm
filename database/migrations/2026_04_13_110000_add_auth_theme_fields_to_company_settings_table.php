<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('company_settings', 'auth_primary_color')) {
                $table->string('auth_primary_color', 16)->nullable()->after('logo_path');
            }
            if (! Schema::hasColumn('company_settings', 'auth_primary_hover_color')) {
                $table->string('auth_primary_hover_color', 16)->nullable()->after('auth_primary_color');
            }
            if (! Schema::hasColumn('company_settings', 'auth_background_from_color')) {
                $table->string('auth_background_from_color', 16)->nullable()->after('auth_primary_hover_color');
            }
            if (! Schema::hasColumn('company_settings', 'auth_background_to_color')) {
                $table->string('auth_background_to_color', 16)->nullable()->after('auth_background_from_color');
            }
            if (! Schema::hasColumn('company_settings', 'auth_card_background_color')) {
                $table->string('auth_card_background_color', 16)->nullable()->after('auth_background_to_color');
            }
            if (! Schema::hasColumn('company_settings', 'auth_card_border_color')) {
                $table->string('auth_card_border_color', 16)->nullable()->after('auth_card_background_color');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table) {
            foreach ([
                'auth_primary_color',
                'auth_primary_hover_color',
                'auth_background_from_color',
                'auth_background_to_color',
                'auth_card_background_color',
                'auth_card_border_color',
            ] as $column) {
                if (Schema::hasColumn('company_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};


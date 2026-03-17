<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->longText('cancellation_policy_traditional')->nullable()->after('cancellation_policy');
            $table->longText('cancellation_policy_simplified')->nullable()->after('cancellation_policy_traditional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['cancellation_policy_traditional', 'cancellation_policy_simplified']);
        });
    }
};

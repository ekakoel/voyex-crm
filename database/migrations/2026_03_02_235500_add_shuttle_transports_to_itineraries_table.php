<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            if (! Schema::hasColumn('itineraries', 'arrival_transport_id')) {
                $table->foreignId('arrival_transport_id')
                    ->nullable()
                    ->after('destination')
                    ->constrained('transports')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('itineraries', 'departure_transport_id')) {
                $table->foreignId('departure_transport_id')
                    ->nullable()
                    ->after('arrival_transport_id')
                    ->constrained('transports')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            if (Schema::hasColumn('itineraries', 'departure_transport_id')) {
                $table->dropConstrainedForeignId('departure_transport_id');
            }
            if (Schema::hasColumn('itineraries', 'arrival_transport_id')) {
                $table->dropConstrainedForeignId('arrival_transport_id');
            }
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('pax_adult')->default(0)->after('travel_date');
            $table->unsignedInteger('pax_child')->default(0)->after('pax_adult');
            $table->json('itinerary_snapshot')->nullable()->after('status');
        });

        $lastId = 0;
        do {
            $rows = DB::table('bookings as b')
                ->join('quotations as q', 'q.id', '=', 'b.quotation_id')
                ->leftJoin('itineraries as i', 'i.id', '=', 'q.itinerary_id')
                ->leftJoin('destinations as d', 'd.id', '=', 'i.destination_id')
                ->where('b.id', '>', $lastId)
                ->orderBy('b.id')
                ->limit(200)
                ->get([
                    'b.id as booking_id',
                    'q.pax_adult',
                    'q.pax_child',
                    'i.id as itinerary_id',
                    'i.title as itinerary_title',
                    'i.destination_id',
                    'd.name as destination_name',
                    'i.duration_days',
                    'i.duration_nights',
                ]);

            foreach ($rows as $row) {
                $snapshot = null;
                if (! empty($row->itinerary_id)) {
                    $snapshot = [
                        'id' => (int) $row->itinerary_id,
                        'title' => (string) ($row->itinerary_title ?? ''),
                        'destination_id' => ! empty($row->destination_id) ? (int) $row->destination_id : null,
                        'destination_name' => (string) ($row->destination_name ?? ''),
                        'duration_days' => ! empty($row->duration_days) ? (int) $row->duration_days : null,
                        'duration_nights' => ! empty($row->duration_nights) ? (int) $row->duration_nights : null,
                        'snapshot_at' => now()->toIso8601String(),
                    ];
                }

                DB::table('bookings')
                    ->where('id', (int) $row->booking_id)
                    ->update([
                        'pax_adult' => max(0, (int) ($row->pax_adult ?? 0)),
                        'pax_child' => max(0, (int) ($row->pax_child ?? 0)),
                        'itinerary_snapshot' => $snapshot ? json_encode($snapshot) : null,
                    ]);
            }

            $lastId = (int) ($rows->last()->booking_id ?? 0);
        } while ($rows->isNotEmpty());
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pax_adult', 'pax_child', 'itinerary_snapshot']);
        });
    }
};


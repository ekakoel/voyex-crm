<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotation_items')) {
            return;
        }

        Schema::table('quotation_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotation_items', 'sort_order')) {
                $table->unsignedInteger('sort_order')->nullable()->after('day_number');
            }
        });

        $quotationIds = DB::table('quotation_items')
            ->select('quotation_id')
            ->whereNotNull('quotation_id')
            ->distinct()
            ->orderBy('quotation_id')
            ->pluck('quotation_id');

        foreach ($quotationIds as $quotationId) {
            $rows = DB::table('quotation_items')
                ->where('quotation_id', $quotationId)
                ->get(['id', 'day_number', 'serviceable_meta']);

            $items = $rows->map(function (object $row): array {
                $meta = [];
                $rawMeta = $row->serviceable_meta ?? null;

                if (is_string($rawMeta) && trim($rawMeta) !== '') {
                    $decoded = json_decode($rawMeta, true);
                    if (is_array($decoded)) {
                        $meta = $decoded;
                    }
                } elseif (is_array($rawMeta)) {
                    $meta = $rawMeta;
                }

                return [
                    'id' => (int) $row->id,
                    'day_number' => (int) ($row->day_number ?? 0),
                    'serviceable_meta' => $meta,
                ];
            })->all();

            usort($items, function (array $left, array $right): int {
                $leftDay = (int) ($left['day_number'] ?? 0);
                $rightDay = (int) ($right['day_number'] ?? 0);

                $leftDayRank = $leftDay > 0 ? 0 : 1;
                $rightDayRank = $rightDay > 0 ? 0 : 1;
                if ($leftDayRank !== $rightDayRank) {
                    return $leftDayRank <=> $rightDayRank;
                }

                if ($leftDayRank === 0 && $leftDay !== $rightDay) {
                    return $leftDay <=> $rightDay;
                }

                $leftMeta = is_array($left['serviceable_meta'] ?? null) ? $left['serviceable_meta'] : [];
                $rightMeta = is_array($right['serviceable_meta'] ?? null) ? $right['serviceable_meta'] : [];

                $leftVisitOrder = isset($leftMeta['visit_order']) && is_numeric($leftMeta['visit_order'])
                    ? (int) $leftMeta['visit_order']
                    : PHP_INT_MAX;
                $rightVisitOrder = isset($rightMeta['visit_order']) && is_numeric($rightMeta['visit_order'])
                    ? (int) $rightMeta['visit_order']
                    : PHP_INT_MAX;
                if ($leftVisitOrder !== $rightVisitOrder) {
                    return $leftVisitOrder <=> $rightVisitOrder;
                }

                $normalizeTimeToMinutes = static function ($value): int {
                    $time = trim((string) $value);
                    if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
                        return PHP_INT_MAX;
                    }

                    return ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);
                };

                $leftStartMinutes = $normalizeTimeToMinutes($leftMeta['start_time'] ?? null);
                $rightStartMinutes = $normalizeTimeToMinutes($rightMeta['start_time'] ?? null);
                if ($leftStartMinutes !== $rightStartMinutes) {
                    return $leftStartMinutes <=> $rightStartMinutes;
                }

                return (int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0);
            });

            foreach (array_values($items) as $index => $item) {
                DB::table('quotation_items')
                    ->where('id', (int) $item['id'])
                    ->update([
                        'sort_order' => $index + 1,
                    ]);
            }
        }

        Schema::table('quotation_items', function (Blueprint $table): void {
            $table->index(['quotation_id', 'sort_order'], 'quotation_items_quotation_sort_order_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotation_items')) {
            return;
        }

        Schema::table('quotation_items', function (Blueprint $table): void {
            if (Schema::hasColumn('quotation_items', 'sort_order')) {
                $table->dropIndex('quotation_items_quotation_sort_order_idx');
                $table->dropColumn('sort_order');
            }
        });
    }
};

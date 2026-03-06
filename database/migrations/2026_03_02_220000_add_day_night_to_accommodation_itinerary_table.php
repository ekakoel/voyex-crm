<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $table = 'accommodation_itinerary';
    private string $fkItinerary = 'accommodation_itinerary_itinerary_id_foreign';
    private string $fkAccommodation = 'accommodation_itinerary_accommodation_id_foreign';
    private string $legacyUnique = 'accommodation_itinerary_itinerary_id_accommodation_id_unique';
    private string $itineraryIdx = 'accom_itinerary_id_idx';
    private string $accommodationIdx = 'accom_accommodation_id_idx';
    private string $newUnique = 'accom_itinerary_unique';

    public function up(): void
    {
        if (! Schema::hasColumn($this->table, 'day_number') || ! Schema::hasColumn($this->table, 'night_count')) {
            Schema::table($this->table, function (Blueprint $table) {
                if (! Schema::hasColumn('accommodation_itinerary', 'day_number')) {
                    $table->unsignedInteger('day_number')->default(1)->after('accommodation_id');
                }
                if (! Schema::hasColumn('accommodation_itinerary', 'night_count')) {
                    $table->unsignedInteger('night_count')->default(1)->after('day_number');
                }
            });
        }

        if ($this->foreignExists($this->fkItinerary)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP FOREIGN KEY `{$this->fkItinerary}`");
        }
        if ($this->foreignExists($this->fkAccommodation)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP FOREIGN KEY `{$this->fkAccommodation}`");
        }
        if ($this->indexExists($this->legacyUnique)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `{$this->legacyUnique}`");
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (! $this->indexExists($this->itineraryIdx)) {
                $table->index('itinerary_id', $this->itineraryIdx);
            }
            if (! $this->indexExists($this->accommodationIdx)) {
                $table->index('accommodation_id', $this->accommodationIdx);
            }
            if (! $this->indexExists($this->newUnique)) {
                $table->unique(['itinerary_id', 'accommodation_id', 'day_number'], $this->newUnique);
            }
        });

        Schema::table($this->table, function (Blueprint $table) {
            if (! $this->foreignExists($this->fkItinerary)) {
                $table->foreign('itinerary_id', $this->fkItinerary)->references('id')->on('itineraries')->cascadeOnDelete();
            }
            if (! $this->foreignExists($this->fkAccommodation)) {
                $table->foreign('accommodation_id', $this->fkAccommodation)->references('id')->on('accommodations')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        if ($this->foreignExists($this->fkItinerary)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP FOREIGN KEY `{$this->fkItinerary}`");
        }
        if ($this->foreignExists($this->fkAccommodation)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP FOREIGN KEY `{$this->fkAccommodation}`");
        }
        if ($this->indexExists($this->newUnique)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `{$this->newUnique}`");
        }
        if ($this->indexExists($this->itineraryIdx)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `{$this->itineraryIdx}`");
        }
        if ($this->indexExists($this->accommodationIdx)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `{$this->accommodationIdx}`");
        }

        Schema::table($this->table, function (Blueprint $table) {
            if (Schema::hasColumn('accommodation_itinerary', 'day_number')) {
                $table->dropColumn('day_number');
            }
            if (Schema::hasColumn('accommodation_itinerary', 'night_count')) {
                $table->dropColumn('night_count');
            }
        });

        Schema::table($this->table, function (Blueprint $table) {
            if (! $this->indexExists($this->legacyUnique)) {
                $table->unique(['itinerary_id', 'accommodation_id'], $this->legacyUnique);
            }
            if (! $this->foreignExists($this->fkItinerary)) {
                $table->foreign('itinerary_id', $this->fkItinerary)->references('id')->on('itineraries')->cascadeOnDelete();
            }
            if (! $this->foreignExists($this->fkAccommodation)) {
                $table->foreign('accommodation_id', $this->fkAccommodation)->references('id')->on('accommodations')->cascadeOnDelete();
            }
        });
    }

    private function indexExists(string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $this->table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignExists(string $constraintName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $this->table)
            ->where('constraint_type', 'FOREIGN KEY')
            ->where('constraint_name', $constraintName)
            ->exists();
    }
};

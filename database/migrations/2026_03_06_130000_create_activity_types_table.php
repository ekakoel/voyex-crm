<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 130)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $existingTypes = DB::table('activities')
            ->select('activity_type')
            ->whereNotNull('activity_type')
            ->where('activity_type', '!=', '')
            ->distinct()
            ->pluck('activity_type')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();

        $usedSlugs = [];
        foreach ($existingTypes as $name) {
            $baseSlug = Str::slug($name);
            if ($baseSlug === '') {
                $baseSlug = 'activity-type';
            }

            $slug = $baseSlug;
            $counter = 2;
            while (isset($usedSlugs[$slug])) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $usedSlugs[$slug] = true;

            DB::table('activity_types')->insert([
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};


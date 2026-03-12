<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('symbol', 10)->nullable();
            $table->decimal('rate_to_idr', 18, 6)->default(1);
            $table->unsignedTinyInteger('decimal_places')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        if (DB::table('currencies')->count() === 0) {
            DB::table('currencies')->insert([
                [
                    'code' => 'IDR',
                    'name' => 'Indonesian Rupiah',
                    'symbol' => 'Rp',
                    'rate_to_idr' => 1,
                    'decimal_places' => 0,
                    'is_active' => true,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => 'USD',
                    'name' => 'US Dollar',
                    'symbol' => '$',
                    'rate_to_idr' => 16000,
                    'decimal_places' => 2,
                    'is_active' => true,
                    'is_default' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};

<?php

namespace Tests\Feature;

use App\Models\Destination;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ItineraryDestinationSuggestionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('destinations');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('tourist_attractions');
        Schema::dropIfExists('food_beverages');
        Schema::dropIfExists('vendors');
        Schema::create('destinations', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('destination_id')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')->nullable();
            $table->string('name');
            $table->integer('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tourist_attractions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('destination_id')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('location')->nullable();
            $table->string('source')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('ideal_visit_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('food_beverages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendor_id')->nullable();
            $table->string('name');
            $table->integer('duration_minutes')->nullable();
            $table->string('meal_period')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('food_beverages');
        Schema::dropIfExists('tourist_attractions');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('destinations');

        parent::tearDown();
    }

    public function test_destination_suggestions_return_every_database_destination_name_without_a_cap(): void
    {
        $this->withoutMiddleware();

        for ($index = 1; $index <= 60; $index++) {
            Destination::query()->create([
                'code' => sprintf('DST%03d', $index),
                'name' => sprintf('Destination %03d', $index),
                'slug' => sprintf('destination-%03d', $index),
                'city' => 'City ' . $index,
                'province' => 'Province ' . $index,
                'country' => 'Indonesia',
                'is_active' => $index % 2 === 0,
            ]);
        }

        $response = $this->getJson(route('itineraries.destination-suggestions'));

        $response->assertOk();
        $suggestions = $response->json('data');

        $this->assertCount(60, $suggestions);
        $this->assertContains('Destination 001', $suggestions);
        $this->assertContains('Destination 060', $suggestions);
    }

    public function test_destination_suggestions_filter_by_typed_name_city_or_province(): void
    {
        $this->withoutMiddleware();

        Destination::query()->create([
            'code' => 'DPS',
            'name' => 'Bali',
            'slug' => 'bali',
            'city' => 'Denpasar',
            'province' => 'Bali Province',
            'country' => 'Indonesia',
            'is_active' => true,
        ]);

        Destination::query()->create([
            'code' => 'LOP',
            'name' => 'Lombok',
            'slug' => 'lombok',
            'city' => 'Mataram',
            'province' => 'West Nusa Tenggara',
            'country' => 'Indonesia',
            'is_active' => true,
        ]);

        $this->getJson(route('itineraries.destination-suggestions', ['q' => 'mata']))
            ->assertOk()
            ->assertJsonPath('data', ['Lombok']);

        $this->getJson(route('itineraries.destination-suggestions', ['q' => 'denpasar']))
            ->assertOk()
            ->assertJsonPath('data', ['Bali']);
    }

    public function test_activity_suggestions_return_every_matching_item_without_a_cap(): void
    {
        $this->withoutMiddleware();
        $vendorId = $this->seedSuggestionVendor();

        for ($index = 1; $index <= 60; $index++) {
            DB::table('activities')->insert([
                'vendor_id' => $vendorId,
                'name' => sprintf('Balinese Activity %03d', $index),
                'duration_minutes' => 60,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->getJson(route('itineraries.activity-suggestions', ['q' => 'Balinese']));

        $response->assertOk();
        $this->assertCount(60, $response->json('data'));
        $this->assertSame('Balinese Activity 001', $response->json('data.0.name'));
        $this->assertSame('Balinese Activity 060', $response->json('data.59.name'));
    }

    public function test_tourist_attraction_suggestions_return_every_matching_item_without_a_cap(): void
    {
        $this->withoutMiddleware();
        $destinationId = $this->seedSuggestionDestination();

        for ($index = 1; $index <= 60; $index++) {
            DB::table('tourist_attractions')->insert([
                'destination_id' => $destinationId,
                'name' => sprintf('Bali Attraction %03d', $index),
                'city' => 'Denpasar',
                'province' => 'Bali',
                'location' => 'Bali',
                'source' => 'Manual',
                'ideal_visit_minutes' => 90,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->getJson(route('itineraries.tourist-attraction-suggestions', ['q' => 'Bali Attraction']));

        $response->assertOk();
        $this->assertCount(60, $response->json('data'));
        $this->assertSame('Bali Attraction 001', $response->json('data.0.name'));
        $this->assertSame('Bali Attraction 060', $response->json('data.59.name'));
    }

    public function test_food_beverage_suggestions_return_every_matching_item_without_a_cap(): void
    {
        $this->withoutMiddleware();
        $vendorId = $this->seedSuggestionVendor();

        for ($index = 1; $index <= 60; $index++) {
            DB::table('food_beverages')->insert([
                'vendor_id' => $vendorId,
                'name' => sprintf('Bali Restaurant %03d', $index),
                'duration_minutes' => 60,
                'meal_period' => 'Lunch',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->getJson(route('itineraries.food-beverage-suggestions', ['q' => 'Bali Restaurant']));

        $response->assertOk();
        $this->assertCount(60, $response->json('data'));
        $this->assertSame('Bali Restaurant 001', $response->json('data.0.name'));
        $this->assertSame('Bali Restaurant 060', $response->json('data.59.name'));
    }

    private function seedSuggestionDestination(): int
    {
        return DB::table('destinations')->insertGetId([
            'code' => 'BALI',
            'name' => 'Bali',
            'slug' => 'bali',
            'city' => 'Denpasar',
            'province' => 'Bali',
            'country' => 'Indonesia',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedSuggestionVendor(): int
    {
        $destinationId = $this->seedSuggestionDestination();

        return DB::table('vendors')->insertGetId([
            'destination_id' => $destinationId,
            'name' => 'Bali Vendor',
            'city' => 'Denpasar',
            'province' => 'Bali',
            'location' => 'Bali',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

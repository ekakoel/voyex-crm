<?php

namespace Tests\Feature\Modules;

use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureModulePermission;
use App\Http\Middleware\PermissionOrSuperAdmin;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Itinerary;
use App\Models\FoodBeverage;
use App\Models\Quotation;
use App\Models\Transport;
use App\Models\TouristAttraction;
use App\Models\User;
use App\Models\Vendor;
use App\Services\QuotationItinerarySyncService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class QuotationValidationWorkflowTest extends ModuleSmokeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            EnsureModuleEnabled::class,
            EnsureModulePermission::class,
            PermissionOrSuperAdmin::class,
            VerifyCsrfToken::class,
        ]);
    }

    public function test_only_reservation_manager_director_can_open_validation_page(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation] = $this->createQuotationWithValidatableItems($creator, 1);

        foreach (['Reservation', 'Manager', 'Director'] as $roleName) {
            $user = $this->createUserWithRole($roleName);
            $this->actingAs($user)
                ->get(route('quotations.validate.show', $quotation))
                ->assertOk();
        }

        $marketing = $this->createUserWithRole('Marketing');
        $this->actingAs($marketing)
            ->get(route('quotations.validate.show', $quotation))
            ->assertForbidden();
    }

    public function test_save_progress_sets_partial_status_when_only_some_required_items_are_validated(): void
    {
        $actor = $this->createUserWithRole('Manager');
        $creator = $this->createUserWithRole('Marketing');
        [$quotation, $itemIds] = $this->createQuotationWithValidatableItems($creator, 2);

        $firstItemId = $itemIds[0];

        $this->actingAs($actor)
            ->patch(route('quotations.validate.save-progress', $quotation), [
                'items' => [
                    $firstItemId => [
                        'contract_rate' => 125000,
                        'markup_type' => 'fixed',
                        'markup' => 10000,
                        'is_validated' => true,
                    ],
                ],
            ])
            ->assertRedirect(route('quotations.validate.show', $quotation));

        $quotation->refresh();
        $this->assertSame('partial', (string) $quotation->validation_status);
    }

    public function test_validation_item_detail_json_endpoint_returns_payload_for_allowed_role(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation, $itemIds] = $this->createQuotationWithValidatableItems($creator, 1);
        $manager = $this->createUserWithRole('Manager');

        $this->actingAs($manager)
            ->get(route('quotations.validate.item-detail-json', ['quotation' => $quotation, 'item' => $itemIds[0]]))
            ->assertOk()
            ->assertJsonStructure([
                'item' => ['id', 'description', 'serviceable_type', 'contract_rate', 'markup_type', 'markup'],
                'contact' => ['vendor_provider_name', 'contact_name', 'contact_phone', 'contact_email', 'contact_website', 'contact_address'],
                'history',
            ]);
    }

    public function test_validation_item_detail_json_uses_address_from_database_when_override_is_empty(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation, $itemIds] = $this->createQuotationWithValidatableItems($creator, 1);
        $manager = $this->createUserWithRole('Manager');

        $item = $quotation->items()->findOrFail($itemIds[0]);
        $attraction = TouristAttraction::query()->findOrFail((int) $item->serviceable_id);
        $attraction->update([
            'address' => 'Jl. Database Address No. 12',
        ]);

        $item->update([
            'serviceable_meta' => array_merge((array) ($item->serviceable_meta ?? []), [
                'validation_contact' => [
                    'contact_name' => null,
                    'contact_phone' => null,
                    'contact_email' => null,
                    'contact_website' => null,
                    'contact_address' => null,
                ],
            ]),
        ]);

        $this->actingAs($manager)
            ->get(route('quotations.validate.item-detail-json', ['quotation' => $quotation, 'item' => $itemIds[0]]))
            ->assertOk()
            ->assertJsonPath('contact.contact_address', 'Jl. Database Address No. 12');
    }

    public function test_validation_item_detail_json_ignores_dash_override_for_address(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation, $itemIds] = $this->createQuotationWithValidatableItems($creator, 1);
        $manager = $this->createUserWithRole('Manager');

        $item = $quotation->items()->findOrFail($itemIds[0]);
        $attraction = TouristAttraction::query()->findOrFail((int) $item->serviceable_id);
        $attraction->update([
            'address' => 'Jl. Address dari DB',
        ]);

        $item->update([
            'serviceable_meta' => array_merge((array) ($item->serviceable_meta ?? []), [
                'validation_contact' => [
                    'contact_address' => '-',
                ],
            ]),
        ]);

        $this->actingAs($manager)
            ->get(route('quotations.validate.item-detail-json', ['quotation' => $quotation, 'item' => $itemIds[0]]))
            ->assertOk()
            ->assertJsonPath('contact.contact_address', 'Jl. Address dari DB');
    }

    public function test_validation_item_detail_json_falls_back_to_location_when_address_empty(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation, $itemIds] = $this->createQuotationWithValidatableItems($creator, 1);
        $manager = $this->createUserWithRole('Manager');

        $item = $quotation->items()->findOrFail($itemIds[0]);
        $attraction = TouristAttraction::query()->findOrFail((int) $item->serviceable_id);
        $attraction->update([
            'address' => null,
            'location' => 'Location Fallback Address',
        ]);

        $this->actingAs($manager)
            ->get(route('quotations.validate.item-detail-json', ['quotation' => $quotation, 'item' => $itemIds[0]]))
            ->assertOk()
            ->assertJsonPath('contact.contact_address', 'Location Fallback Address');
    }

    public function test_validation_item_detail_json_uses_vendor_location_when_vendor_address_empty(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $creator = $this->createUserWithRole('Marketing');
        $customer = $this->createCustomer(['created_by' => $creator->id]);
        $inquiry = $this->createInquiry($customer, [
            'created_by' => $creator->id,
            'assigned_to' => $creator->id,
        ]);
        $itinerary = Itinerary::query()->create([
            'inquiry_id' => $inquiry->id,
            'created_by' => $creator->id,
            'title' => 'Vendor Location Itinerary ' . Str::random(4),
            'destination' => 'Bali',
            'duration_days' => 2,
            'duration_nights' => 1,
            'status' => 'pending',
            'is_active' => true,
        ]);
        $quotation = Quotation::query()->create([
            'quotation_number' => 'QT-VND-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'inquiry_id' => $itinerary->inquiry_id,
            'itinerary_id' => $itinerary->id,
            'status' => 'pending',
            'validation_status' => 'pending',
            'validity_date' => now()->addDays(7)->toDateString(),
            'sub_total' => 100000,
            'discount_type' => null,
            'discount_value' => 0,
            'final_amount' => 100000,
        ]);
        $quotation->forceFill([
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ])->save();

        $vendor = Vendor::query()->create([
            'name' => 'Vendor Address Fallback',
            'location' => 'Vendor Location Field',
            'address' => null,
            'is_active' => true,
        ]);
        $food = FoodBeverage::query()->create([
            'vendor_id' => $vendor->id,
            'name' => 'F&B Vendor Location',
            'service_type' => 'meal',
            'contract_rate' => 100000,
            'markup_type' => 'fixed',
            'markup' => 10000,
            'publish_rate' => 110000,
            'is_active' => true,
        ]);

        $item = $quotation->items()->create([
            'description' => 'F&B Item',
            'qty' => 1,
            'contract_rate' => 100000,
            'markup_type' => 'fixed',
            'markup' => 10000,
            'unit_price' => 110000,
            'discount_type' => 'fixed',
            'discount' => 0,
            'total' => 110000,
            'serviceable_type' => FoodBeverage::class,
            'serviceable_id' => $food->id,
            'itinerary_item_type' => 'fnb',
            'is_validation_required' => true,
            'is_validated' => false,
        ]);

        $this->actingAs($manager)
            ->get(route('quotations.validate.item-detail-json', ['quotation' => $quotation, 'item' => $item->id]))
            ->assertOk()
            ->assertJsonPath('contact.contact_address', 'Vendor Location Field');
    }

    public function test_validation_item_detail_json_uses_vendor_contact_for_transport_model(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $creator = $this->createUserWithRole('Marketing');
        $customer = $this->createCustomer(['created_by' => $creator->id]);
        $inquiry = $this->createInquiry($customer, [
            'created_by' => $creator->id,
            'assigned_to' => $creator->id,
        ]);
        $itinerary = Itinerary::query()->create([
            'inquiry_id' => $inquiry->id,
            'created_by' => $creator->id,
            'title' => 'Transport Vendor Contact Itinerary ' . Str::random(4),
            'destination' => 'Bali',
            'duration_days' => 2,
            'duration_nights' => 1,
            'status' => 'pending',
            'is_active' => true,
        ]);
        $quotation = Quotation::query()->create([
            'quotation_number' => 'QT-TRN-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'inquiry_id' => $itinerary->inquiry_id,
            'itinerary_id' => $itinerary->id,
            'status' => 'pending',
            'validation_status' => 'pending',
            'validity_date' => now()->addDays(7)->toDateString(),
            'sub_total' => 1500000,
            'discount_type' => null,
            'discount_value' => 0,
            'final_amount' => 1500000,
        ]);
        $quotation->forceFill([
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ])->save();

        $vendor = Vendor::query()->create([
            'name' => 'Vendor Transport',
            'contact_name' => 'Andi Driver',
            'contact_phone' => '081298765432',
            'contact_email' => 'andi@vendor-transport.test',
            'website' => 'https://vendor-transport.test',
            'address' => 'Jl. Vendor Transport No. 88',
            'is_active' => true,
        ]);
        $transport = Transport::query()->create([
            'name' => 'HiAce',
            'vendor_id' => $vendor->id,
            'contract_rate' => 1500000,
            'markup_type' => 'fixed',
            'markup' => 250000,
            'publish_rate' => 1750000,
            'is_active' => true,
        ]);
        $item = $quotation->items()->create([
            'description' => 'Day 1 - Transport: HiAce',
            'qty' => 1,
            'contract_rate' => 1500000,
            'markup_type' => 'fixed',
            'markup' => 250000,
            'unit_price' => 1750000,
            'discount_type' => 'fixed',
            'discount' => 0,
            'total' => 1750000,
            'serviceable_type' => Transport::class,
            'serviceable_id' => $transport->id,
            'itinerary_item_type' => 'transport_day',
            'is_validation_required' => true,
            'is_validated' => false,
        ]);

        $this->actingAs($manager)
            ->get(route('quotations.validate.item-detail-json', ['quotation' => $quotation, 'item' => $item->id]))
            ->assertOk()
            ->assertJsonPath('contact.vendor_provider_name', 'Vendor Transport')
            ->assertJsonPath('contact.contact_name', 'Andi Driver')
            ->assertJsonPath('contact.contact_phone', '081298765432')
            ->assertJsonPath('contact.contact_email', 'andi@vendor-transport.test')
            ->assertJsonPath('contact.contact_address', 'Jl. Vendor Transport No. 88');
    }

    public function test_validation_item_contact_can_be_updated_via_ajax_without_reload(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation, $itemIds] = $this->createQuotationWithValidatableItems($creator, 1);
        $manager = $this->createUserWithRole('Manager');

        $payload = [
            'contact_name' => 'Rina Vendor',
            'contact_phone' => '081122334455',
            'contact_email' => 'rina.vendor@example.com',
            'contact_website' => 'https://vendor.example.com',
            'contact_address' => 'Jl. Merdeka No. 99, Jakarta',
        ];

        $this->actingAs($manager)
            ->patchJson(route('quotations.validate.update-item-contact', [
                'quotation' => $quotation,
                'item' => $itemIds[0],
            ]), $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Contact details updated.')
            ->assertJsonPath('contact.contact_name', 'Rina Vendor')
            ->assertJsonPath('contact.contact_email', 'rina.vendor@example.com')
            ->assertJsonPath('contact.contact_website', 'https://vendor.example.com');
    }

    public function test_validate_page_syncs_item_rate_from_updated_master_module_data(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation, $itemIds] = $this->createQuotationWithValidatableItems($creator, 1);
        $manager = $this->createUserWithRole('Manager');

        $item = $quotation->items()->findOrFail($itemIds[0]);
        $attraction = TouristAttraction::query()->findOrFail((int) $item->serviceable_id);
        $attraction->update([
            'contract_rate_per_pax' => 2880000,
            'markup_type' => 'fixed',
            'markup' => 150000,
            'publish_rate_per_pax' => 3030000,
        ]);

        $this->actingAs($manager)
            ->get(route('quotations.validate.show', $quotation))
            ->assertOk();

        $item->refresh();
        $this->assertSame('2880000.00', (string) $item->contract_rate);
        $this->assertSame('fixed', (string) $item->markup_type);
        $this->assertSame('150000.00', (string) $item->markup);
    }

    public function test_validate_page_shows_qty_from_quotation_item(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation, $itemIds] = $this->createQuotationWithValidatableItems($creator, 1);
        $manager = $this->createUserWithRole('Manager');

        $item = $quotation->items()->findOrFail($itemIds[0]);
        $item->update([
            'qty' => 7,
        ]);

        $this->actingAs($manager)
            ->get(route('quotations.validate.show', $quotation))
            ->assertOk()
            ->assertSee('>7<', false);
    }

    public function test_approval_is_blocked_when_validation_not_completed(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation] = $this->createQuotationWithValidatableItems($creator, 1);
        $manager = $this->createUserWithRole('Manager');

        $this->actingAs($manager)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation))
            ->assertSessionHas('error', 'Quotation cannot be approved because validation is not completed.');
    }

    public function test_validated_quotation_can_enter_approval_flow(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation, $itemIds] = $this->createQuotationWithValidatableItems($creator, 1);
        $manager = $this->createUserWithRole('Manager');

        $itemId = $itemIds[0];

        $this->actingAs($manager)
            ->patch(route('quotations.validate.save-progress', $quotation), [
                'items' => [
                    $itemId => [
                        'contract_rate' => 100000,
                        'markup_type' => 'fixed',
                        'markup' => 20000,
                        'is_validated' => true,
                    ],
                ],
            ])
            ->assertRedirect(route('quotations.validate.show', $quotation));

        $this->actingAs($manager)
            ->post(route('quotations.validate.finalize', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertSame('valid', (string) $quotation->validation_status);

        $this->actingAs($manager)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));
    }

    public function test_itinerary_sync_service_updates_linked_pending_quotation_items(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        [$quotation] = $this->createQuotationWithValidatableItems($creator, 1);
        $itinerary = $quotation->itinerary()->firstOrFail();

        $oldAttraction = TouristAttraction::query()->findOrFail((int) ($quotation->items()->firstOrFail()->serviceable_id ?? 0));
        $newAttraction = TouristAttraction::query()->create([
            'name' => 'Updated Itinerary Attraction ' . Str::random(4),
            'contract_rate_per_pax' => 410000,
            'markup_type' => 'fixed',
            'markup' => 90000,
            'publish_rate_per_pax' => 500000,
            'is_active' => true,
        ]);

        $itinerary->touristAttractions()->sync([
            $newAttraction->id => ['day_number' => 1],
        ]);

        $quotation->update([
            'status' => 'processed',
            'validation_status' => 'valid',
        ]);

        app(QuotationItinerarySyncService::class)->syncLinkedQuotationFromItinerary($itinerary->fresh());

        $quotation->refresh();
        $this->assertSame('pending', (string) $quotation->status);
        $this->assertSame('pending', (string) $quotation->validation_status);
        $this->assertTrue(
            $quotation->items()->where('serviceable_id', $newAttraction->id)->exists()
        );
        $this->assertFalse(
            $quotation->items()->where('serviceable_id', $oldAttraction->id)->exists()
        );
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user = User::query()->create([
            'name' => "Validation {$roleName} " . Str::random(4),
            'email' => 'validation-' . Str::lower($roleName) . '-' . Str::random(6) . '@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole($role->name);

        return $user;
    }

    /**
     * @return array{0:Quotation,1:array<int,int>}
     */
    private function createQuotationWithValidatableItems(User $creator, int $itemCount): array
    {
        $customer = $this->createCustomer(['created_by' => $creator->id]);
        $inquiry = $this->createInquiry($customer, [
            'created_by' => $creator->id,
            'assigned_to' => $creator->id,
        ]);

        $itinerary = Itinerary::query()->create([
            'inquiry_id' => $inquiry->id,
            'created_by' => $creator->id,
            'title' => 'Validation Itinerary ' . Str::random(4),
            'destination' => 'Bali',
            'duration_days' => 2,
            'duration_nights' => 1,
            'status' => 'pending',
            'is_active' => true,
        ]);

        $quotationData = [
            'quotation_number' => 'QT-VLD-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'inquiry_id' => $itinerary->inquiry_id,
            'itinerary_id' => $itinerary->id,
            'status' => 'pending',
            'validity_date' => now()->addDays(7)->toDateString(),
            'sub_total' => 100000,
            'discount_type' => null,
            'discount_value' => 0,
            'final_amount' => 100000,
        ];
        if (Schema::hasColumn('quotations', 'validation_status')) {
            $quotationData['validation_status'] = 'pending';
        }

        $quotation = Quotation::query()->create($quotationData);
        $quotation->forceFill([
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ])->save();

        $itemIds = [];
        for ($i = 1; $i <= $itemCount; $i++) {
            $attraction = TouristAttraction::query()->create([
                'name' => 'Validation Attraction ' . Str::random(4),
            ]);

            $itemData = [
                'description' => 'Attraction Item ' . $i,
                'qty' => 1,
                'contract_rate' => 100000,
                'markup_type' => 'fixed',
                'markup' => 10000,
                'unit_price' => 110000,
                'discount_type' => 'fixed',
                'discount' => 0,
                'total' => 110000,
                'serviceable_type' => TouristAttraction::class,
                'serviceable_id' => $attraction->id,
                'itinerary_item_type' => 'attraction',
            ];
            if (Schema::hasColumn('quotation_items', 'is_validation_required')) {
                $itemData['is_validation_required'] = true;
            }
            if (Schema::hasColumn('quotation_items', 'is_validated')) {
                $itemData['is_validated'] = false;
            }

            $item = $quotation->items()->create($itemData);

            $itemIds[] = (int) $item->id;
        }

        return [$quotation->fresh('items'), $itemIds];
    }
}

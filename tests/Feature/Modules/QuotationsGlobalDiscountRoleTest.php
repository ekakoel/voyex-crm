<?php

namespace Tests\Feature\Modules;

use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureModulePermission;
use App\Http\Middleware\PermissionOrSuperAdmin;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class QuotationsGlobalDiscountRoleTest extends ModuleSmokeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            EnsureModuleEnabled::class,
            EnsureModulePermission::class,
            PermissionOrSuperAdmin::class,
        ]);
    }

    public function test_manager_and_director_only_see_global_discount_controls_in_edit_validation_sidebar(): void
    {
        foreach (['Manager', 'Director'] as $roleName) {
            $user = $this->createUserWithRole($roleName);
            $this->actingAs($user);

            $itinerary = $this->createItineraryFor($user);
            $quotation = $this->createQuotationFor($user, $itinerary);

            $this->get(route('quotations.create'))
                ->assertOk()
                ->assertDontSeeText('Global Discount Type')
                ->assertDontSeeText('Global Discount Value')
                ->assertSeeText('Global Discount Amount (Auto)');

            $this->get(route('quotations.edit', $quotation))
                ->assertOk()
                ->assertSeeText('Discount Value')
                ->assertSeeText('Update Global Discount');
        }
    }

    public function test_marketing_cannot_see_global_discount_fields(): void
    {
        $user = $this->createUserWithRole('Marketing');
        $this->actingAs($user);

        $itinerary = $this->createItineraryFor($user);
        $quotation = $this->createQuotationFor($user, $itinerary);

        $this->get(route('quotations.create'))
            ->assertOk()
            ->assertDontSeeText('Global Discount Type')
            ->assertDontSeeText('Global Discount Value')
            ->assertSeeText('Global Discount Amount (Auto)');

        $this->get(route('quotations.edit', $quotation))
            ->assertOk()
            ->assertDontSeeText('Global Discount Type')
            ->assertDontSeeText('Global Discount Value')
            ->assertSeeText('Global Discount Amount (Auto)');
    }

    public function test_manager_can_update_global_discount_values(): void
    {
        $user = $this->createUserWithRole('Manager');
        $this->actingAs($user);

        $itinerary = $this->createItineraryFor($user);
        $quotation = $this->createQuotationFor($user, $itinerary, null, 0);

        $this->put(route('quotations.update', $quotation), [
            'itinerary_id' => $itinerary->id,
            'validity_date' => now()->addDays(5)->format('Y-m-d'),
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'items' => [
                [
                    'description' => 'Updated item',
                    'qty' => 1,
                    'unit_price' => 100000,
                    'discount_type' => 'fixed',
                    'discount' => 0,
                    'itinerary_item_type' => 'manual',
                ],
            ],
        ])->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertSame('fixed', $quotation->discount_type);
        $this->assertSame('5000.00', (string) $quotation->discount_value);
    }

    public function test_marketing_cannot_override_existing_global_discount_on_update(): void
    {
        $user = $this->createUserWithRole('Marketing');
        $this->actingAs($user);

        $itinerary = $this->createItineraryFor($user);
        $quotation = $this->createQuotationFor($user, $itinerary, 'fixed', 7000);

        $this->put(route('quotations.update', $quotation), [
            'itinerary_id' => $itinerary->id,
            'validity_date' => now()->addDays(7)->format('Y-m-d'),
            'discount_type' => 'percent',
            'discount_value' => 99,
            'items' => [
                [
                    'description' => 'Marketing update attempt',
                    'qty' => 1,
                    'unit_price' => 120000,
                    'discount_type' => 'fixed',
                    'discount' => 0,
                    'itinerary_item_type' => 'manual',
                ],
            ],
        ])->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertSame('fixed', $quotation->discount_type);
        $this->assertSame('7000.00', (string) $quotation->discount_value);
    }

    public function test_manager_and_director_can_update_global_discount_from_validation_panel_endpoint(): void
    {
        foreach (['Manager', 'Director'] as $roleName) {
            $user = $this->createUserWithRole($roleName);
            $this->actingAs($user);

            $itinerary = $this->createItineraryFor($user);
            $quotation = $this->createQuotationFor($user, $itinerary, null, 0);

            $this->patch(route('quotations.global-discount', $quotation), [
                'global_discount_type' => 'percent',
                'global_discount_value' => 10,
            ])->assertRedirect(route('quotations.edit', $quotation));

            $quotation->refresh();
            $this->assertSame('percent', $quotation->discount_type);
            $this->assertSame('10.00', (string) $quotation->discount_value);
            $this->assertSame('90000.00', (string) $quotation->final_amount);
        }
    }

    public function test_marketing_cannot_update_global_discount_from_validation_panel_endpoint(): void
    {
        $user = $this->createUserWithRole('Marketing');
        $this->actingAs($user);

        $itinerary = $this->createItineraryFor($user);
        $quotation = $this->createQuotationFor($user, $itinerary, 'fixed', 7000);

        $this->patch(route('quotations.global-discount', $quotation), [
            'global_discount_type' => 'percent',
            'global_discount_value' => 99,
        ])->assertSessionHas('error');

        $quotation->refresh();
        $this->assertSame('fixed', $quotation->discount_type);
        $this->assertSame('7000.00', (string) $quotation->discount_value);
    }

    public function test_manager_and_director_can_access_edit_quotation_created_by_other_user(): void
    {
        $owner = $this->createUserWithRole('Marketing');
        $ownerItinerary = $this->createItineraryFor($owner);
        $quotation = $this->createQuotationFor($owner, $ownerItinerary, null, 0);

        foreach (['Manager', 'Director'] as $roleName) {
            $editor = $this->createUserWithRole($roleName);
            $this->actingAs($editor);

            $this->get(route('quotations.edit', $quotation))
                ->assertOk();
        }
    }

    public function test_marketing_cannot_access_edit_quotation_created_by_other_user(): void
    {
        $owner = $this->createUserWithRole('Marketing');
        $ownerItinerary = $this->createItineraryFor($owner);
        $quotation = $this->createQuotationFor($owner, $ownerItinerary, null, 0);

        $otherMarketing = $this->createUserWithRole('Marketing');
        $this->actingAs($otherMarketing);

        $this->get(route('quotations.edit', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));
    }

    public function test_quotation_requires_manager_director_and_non_creator_reservation_approval(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        $itinerary = $this->createItineraryFor($creator);
        $quotation = $this->createQuotationFor($creator, $itinerary, null, 0);

        $manager = $this->createUserWithRole('Manager');
        $director = $this->createUserWithRole('Director');
        $reservation = $this->createUserWithRole('Reservation');

        $this->actingAs($reservation)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));
        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);

        $this->actingAs($manager)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));
        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);

        $this->actingAs($director)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));
        $quotation->refresh();

        $this->assertSame('approved', $quotation->status);
        $this->assertSame($director->id, (int) $quotation->approved_by);
        $this->assertNotNull($quotation->approved_at);
    }

    public function test_reservation_approval_must_be_from_non_creator_user(): void
    {
        $creator = $this->createUserWithRole('Reservation');
        $itinerary = $this->createItineraryFor($creator);
        $quotation = $this->createQuotationFor($creator, $itinerary, null, 0);

        $manager = $this->createUserWithRole('Manager');
        $director = $this->createUserWithRole('Director');

        $this->actingAs($creator)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));

        $this->actingAs($manager)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));
        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);

        $this->actingAs($director)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));
        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);

        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);

        $otherReservation = $this->createUserWithRole('Reservation');
        $this->actingAs($otherReservation)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);

        $this->actingAs($manager)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));
        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);

        $this->actingAs($director)
            ->post(route('quotations.approve', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));
        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user = User::query()->create([
            'name' => "Test {$roleName} " . Str::random(4),
            'email' => 'test-' . Str::lower($roleName) . '-' . Str::random(6) . '@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole($role->name);

        return $user;
    }

    private function createItineraryFor(User $user): Itinerary
    {
        $customer = $this->createCustomer([
            'created_by' => $user->id,
        ]);
        $inquiry = $this->createInquiry($customer, [
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        return Itinerary::query()->create([
            'inquiry_id' => $inquiry->id,
            'created_by' => $user->id,
            'title' => 'Role Matrix Itinerary ' . Str::random(4),
            'destination' => 'Bandung',
            'duration_days' => 2,
            'duration_nights' => 1,
            'status' => 'draft',
            'is_active' => true,
        ]);
    }

    private function createQuotationFor(User $user, Itinerary $itinerary, ?string $discountType = null, float $discountValue = 0): Quotation
    {
        $quotation = Quotation::query()->create([
            'quotation_number' => 'QT-RM-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'inquiry_id' => $itinerary->inquiry_id,
            'itinerary_id' => $itinerary->id,
            'status' => 'pending',
            'validity_date' => now()->addDays(7)->format('Y-m-d'),
            'sub_total' => 100000,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'final_amount' => max(0, 100000 - $discountValue),
        ]);
        $quotation->forceFill([
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ])->save();

        $quotation->items()->create([
            'description' => 'Seed item',
            'qty' => 1,
            'contract_rate' => 0,
            'markup_type' => 'fixed',
            'markup' => 0,
            'unit_price' => 100000,
            'discount_type' => 'fixed',
            'discount' => 0,
            'total' => 100000,
            'itinerary_item_type' => 'manual',
        ]);

        return $quotation;
    }
}

<?php

namespace Tests\Feature\Modules;

use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsureModulePermission;
use App\Http\Middleware\PermissionOrSuperAdmin;
use App\Http\Middleware\VerifyCsrfToken;
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
            VerifyCsrfToken::class,
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

    public function test_manager_and_director_cannot_access_edit_quotation_created_by_other_user(): void
    {
        $owner = $this->createUserWithRole('Marketing');
        $ownerItinerary = $this->createItineraryFor($owner);
        $quotation = $this->createQuotationFor($owner, $ownerItinerary, null, 0);

        foreach (['Manager', 'Director'] as $roleName) {
            $editor = $this->createUserWithRole($roleName);
            $this->actingAs($editor);

            $this->get(route('quotations.edit', $quotation))
                ->assertRedirect(route('quotations.show', $quotation));
        }
    }

    public function test_manager_cannot_update_global_discount_for_quotation_created_by_other_user(): void
    {
        $owner = $this->createUserWithRole('Marketing');
        $ownerItinerary = $this->createItineraryFor($owner);
        $quotation = $this->createQuotationFor($owner, $ownerItinerary, 'fixed', 5000);

        $manager = $this->createUserWithRole('Manager');
        $this->actingAs($manager)
            ->patch(route('quotations.global-discount', $quotation), [
                'global_discount_type' => 'percent',
                'global_discount_value' => 10,
            ])
            ->assertRedirect(route('quotations.show', $quotation))
            ->assertSessionHas('error');

        $quotation->refresh();
        $this->assertSame('fixed', $quotation->discount_type);
        $this->assertSame('5000.00', (string) $quotation->discount_value);
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

    public function test_index_shows_quotations_across_all_statuses(): void
    {
        $user = $this->createUserWithRole('Marketing');
        $this->actingAs($user);

        $approvedItinerary = $this->createItineraryFor($user);
        $approvedQuotation = $this->createQuotationFor($user, $approvedItinerary, null, 0);
        $approvedQuotation->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $finalItinerary = $this->createItineraryFor($user);
        $finalQuotation = $this->createQuotationFor($user, $finalItinerary, null, 0);
        $finalQuotation->update([
            'status' => 'final',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $pendingItinerary = $this->createItineraryFor($user);
        $pendingQuotation = $this->createQuotationFor($user, $pendingItinerary, null, 0);
        $pendingQuotation->update([
            'status' => 'pending',
        ]);

        $this->get(route('quotations.index'))
            ->assertOk()
            ->assertSeeText($approvedQuotation->quotation_number)
            ->assertSeeText($finalQuotation->quotation_number)
            ->assertSeeText($pendingQuotation->quotation_number);
    }

    public function test_my_quotations_page_shows_all_statuses_created_by_authenticated_user_only(): void
    {
        $owner = $this->createUserWithRole('Marketing');
        $otherUser = $this->createUserWithRole('Marketing');

        $ownerPendingItinerary = $this->createItineraryFor($owner);
        $ownerPendingQuotation = $this->createQuotationFor($owner, $ownerPendingItinerary, null, 0);
        $ownerPendingQuotation->update([
            'status' => 'pending',
        ]);

        $ownerApprovedItinerary = $this->createItineraryFor($owner);
        $ownerApprovedQuotation = $this->createQuotationFor($owner, $ownerApprovedItinerary, null, 0);
        $ownerApprovedQuotation->update([
            'status' => 'approved',
            'approved_by' => $otherUser->id,
            'approved_at' => now(),
        ]);

        $otherItinerary = $this->createItineraryFor($otherUser);
        $otherQuotation = $this->createQuotationFor($otherUser, $otherItinerary, null, 0);
        $otherQuotation->update([
            'status' => 'final',
            'approved_by' => $otherUser->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('quotations.my'))
            ->assertOk()
            ->assertSeeText($ownerPendingQuotation->quotation_number)
            ->assertSeeText($ownerApprovedQuotation->quotation_number)
            ->assertDontSeeText($otherQuotation->quotation_number);
    }

    public function test_quotation_becomes_approved_after_two_non_creator_approvals(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        $itinerary = $this->createItineraryFor($creator);
        $quotation = $this->createQuotationFor($creator, $itinerary, null, 0);

        $manager = $this->createUserWithRole('Manager');
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
        $this->assertSame('approved', $quotation->status);
        $this->assertSame($manager->id, (int) $quotation->approved_by);
        $this->assertNotNull($quotation->approved_at);
    }

    public function test_creator_cannot_approve_and_two_other_users_can_finalize_approval(): void
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
        $this->assertSame('approved', $quotation->status);
    }

    public function test_creator_can_set_approved_quotation_to_final(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        $itinerary = $this->createItineraryFor($creator);
        $quotation = $this->createQuotationFor($creator, $itinerary, null, 0);
        $quotation->update([
            'status' => 'approved',
            'approved_by' => $creator->id,
            'approved_at' => now(),
            'validity_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($creator)
            ->post(route('quotations.set-final', $quotation))
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertSame('final', $quotation->status);
    }

    public function test_non_creator_cannot_set_approved_quotation_to_final(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        $itinerary = $this->createItineraryFor($creator);
        $quotation = $this->createQuotationFor($creator, $itinerary, null, 0);
        $quotation->update([
            'status' => 'approved',
            'approved_by' => $creator->id,
            'approved_at' => now(),
            'validity_date' => now()->addDays(3)->toDateString(),
        ]);

        $manager = $this->createUserWithRole('Manager');
        $this->actingAs($manager)
            ->post(route('quotations.set-final', $quotation))
            ->assertRedirect(route('quotations.show', $quotation))
            ->assertSessionHas('error');

        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
    }

    public function test_expired_approved_quotation_auto_finalizes_on_show(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        $itinerary = $this->createItineraryFor($creator);
        $quotation = $this->createQuotationFor($creator, $itinerary, null, 0);
        $quotation->update([
            'status' => 'approved',
            'approved_by' => $creator->id,
            'approved_at' => now()->subDay(),
            'validity_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($creator)
            ->get(route('quotations.show', $quotation))
            ->assertOk();

        $quotation->refresh();
        $this->assertSame('final', $quotation->status);
    }

    public function test_expired_non_approved_quotation_does_not_auto_change_status(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        $itinerary = $this->createItineraryFor($creator);
        $quotation = $this->createQuotationFor($creator, $itinerary, null, 0);
        $quotation->update([
            'status' => 'pending',
            'validity_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($creator)
            ->get(route('quotations.show', $quotation))
            ->assertOk();

        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);
    }

    public function test_creator_can_update_approved_quotation_and_status_resets_to_pending(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        $itinerary = $this->createItineraryFor($creator);
        $quotation = $this->createQuotationFor($creator, $itinerary, null, 0);
        $quotation->update([
            'status' => 'approved',
            'approved_by' => $creator->id,
            'approved_at' => now(),
            'validity_date' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($creator)
            ->put(route('quotations.update', $quotation), [
                'itinerary_id' => $itinerary->id,
                'validity_date' => now()->addDays(5)->format('Y-m-d'),
                'items' => [
                    [
                        'description' => 'Creator revised approved quotation',
                        'qty' => 2,
                        'unit_price' => 110000,
                        'discount_type' => 'fixed',
                        'discount' => 0,
                        'itinerary_item_type' => 'manual',
                    ],
                ],
            ])
            ->assertRedirect(route('quotations.show', $quotation));

        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);
        $this->assertNull($quotation->approved_by);
        $this->assertNull($quotation->approved_at);
        $this->assertSame('Creator revised approved quotation', (string) $quotation->items()->first()->description);
    }

    public function test_edit_quotation_still_shows_linked_itinerary_when_itinerary_is_final(): void
    {
        $creator = $this->createUserWithRole('Marketing');
        $itinerary = $this->createItineraryFor($creator);
        $quotation = $this->createQuotationFor($creator, $itinerary, null, 0);

        $itinerary->update([
            'status' => Itinerary::FINAL_STATUS,
            'is_active' => false,
        ]);

        $this->actingAs($creator)
            ->get(route('quotations.edit', $quotation))
            ->assertOk()
            ->assertSeeText($itinerary->title)
            ->assertDontSeeText('Belum ada itinerary aktif yang siap dipakai untuk quotation.');
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
            'status' => 'pending',
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

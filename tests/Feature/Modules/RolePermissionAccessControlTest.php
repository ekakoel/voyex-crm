<?php

namespace Tests\Feature\Modules;

use App\Http\View\SidebarComposer;
use App\Models\Booking;
use App\Models\Currency;
use App\Models\Inquiry;
use App\Models\Itinerary;
use App\Models\Module;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_uses_permission_first_for_module_items(): void
    {
        $directorRole = Role::findOrCreate('Director', 'web');
        $user = User::factory()->create();
        $user->assignRole($directorRole);

        Module::query()->updateOrCreate(
            ['key' => 'airports'],
            ['name' => 'Airports', 'description' => 'Airports', 'is_enabled' => true]
        );
        Module::query()->updateOrCreate(
            ['key' => 'user_manager'],
            ['name' => 'User Manager', 'description' => 'Users', 'is_enabled' => true]
        );
        Module::query()->updateOrCreate(
            ['key' => 'role_manager'],
            ['name' => 'Role Manager', 'description' => 'Roles', 'is_enabled' => true]
        );

        $permissions = [
            'module.airports.access',
            'module.user_manager.access',
            'module.role_manager.access',
            'dashboard.director.view',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $user->givePermissionTo($permissions);

        $composer = new SidebarComposer();
        $reflection = new ReflectionClass($composer);
        $getMenuItems = $reflection->getMethod('getMenuItems');
        $getMenuItems->setAccessible(true);
        $filterMenuItems = $reflection->getMethod('filterMenuItems');
        $filterMenuItems->setAccessible(true);

        $menuItems = $getMenuItems->invoke($composer);
        $filtered = $filterMenuItems->invoke($composer, $menuItems, $user);

        $routes = [];
        $collectRoutes = function (array $items) use (&$routes, &$collectRoutes): void {
            foreach ($items as $item) {
                if (! empty($item['route']) && $item['route'] !== '#') {
                    $routes[] = (string) $item['route'];
                }
                if (! empty($item['children']) && is_array($item['children'])) {
                    $collectRoutes($item['children']);
                }
            }
        };
        $collectRoutes($filtered);

        $this->assertContains('airports.index', $routes);
        $this->assertContains('users.index', $routes);
        $this->assertContains('roles.index', $routes);
    }

    public function test_user_with_delete_permission_can_delete_currency(): void
    {
        Role::findOrCreate('Director', 'web');
        $user = User::factory()->create();
        $user->assignRole('Director');

        Module::query()->updateOrCreate(
            ['key' => 'currencies'],
            ['name' => 'Currencies', 'description' => 'Currency module', 'is_enabled' => true]
        );

        Permission::findOrCreate('module.currencies.access', 'web');
        Permission::findOrCreate('module.currencies.delete', 'web');
        $user->givePermissionTo(['module.currencies.access', 'module.currencies.delete']);

        Currency::query()->firstOrCreate([
            'code' => 'IDR',
        ], [
            'name' => 'Rupiah',
            'symbol' => 'Rp',
            'rate_to_idr' => 1,
            'decimal_places' => 0,
            'is_active' => true,
            'is_default' => true,
        ]);
        $currency = Currency::query()->create([
            'code' => 'ZZZ',
            'name' => 'Test Currency',
            'symbol' => 'T',
            'rate_to_idr' => 12345,
            'decimal_places' => 0,
            'is_active' => true,
            'is_default' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('currencies.destroy', $currency));

        $response->assertRedirect();
        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_quotation_update_delete_policy_is_permission_driven(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('module.quotations.update', 'web');
        Permission::findOrCreate('module.quotations.delete', 'web');

        $quotation = new Quotation();

        $this->assertFalse($user->can('update', $quotation));
        $this->assertFalse($user->can('delete', $quotation));

        $user->givePermissionTo('module.quotations.update');
        $this->assertTrue($user->can('update', $quotation));
        $this->assertFalse($user->can('delete', $quotation));

        $user->givePermissionTo('module.quotations.delete');
        $this->assertTrue($user->can('delete', $quotation));
    }

    public function test_itinerary_update_delete_policy_is_permission_driven(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('module.itineraries.update', 'web');
        Permission::findOrCreate('module.itineraries.delete', 'web');

        $itinerary = new Itinerary();

        $this->assertFalse($user->can('update', $itinerary));
        $this->assertFalse($user->can('delete', $itinerary));

        $user->givePermissionTo('module.itineraries.update');
        $this->assertTrue($user->can('update', $itinerary));

        $user->givePermissionTo('module.itineraries.delete');
        $this->assertTrue($user->can('delete', $itinerary));
    }

    public function test_inquiry_update_delete_policy_is_permission_driven(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('module.inquiries.update', 'web');
        Permission::findOrCreate('module.inquiries.delete', 'web');

        $inquiry = new Inquiry();

        $this->assertFalse($user->can('update', $inquiry));
        $this->assertFalse($user->can('delete', $inquiry));

        $user->givePermissionTo('module.inquiries.update');
        $this->assertTrue($user->can('update', $inquiry));

        $user->givePermissionTo('module.inquiries.delete');
        $this->assertTrue($user->can('delete', $inquiry));
    }

    public function test_booking_update_delete_policy_is_permission_driven(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('module.bookings.update', 'web');
        Permission::findOrCreate('module.bookings.delete', 'web');

        $booking = new Booking();

        $this->assertFalse($user->can('update', $booking));
        $this->assertFalse($user->can('delete', $booking));

        $user->givePermissionTo('module.bookings.update');
        $this->assertTrue($user->can('update', $booking));

        $user->givePermissionTo('module.bookings.delete');
        $this->assertTrue($user->can('delete', $booking));
    }

    public function test_dashboard_redirect_uses_permission_priority(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('dashboard.manager.view', 'web');
        Permission::findOrCreate('dashboard.director.view', 'web');
        $user->givePermissionTo(['dashboard.manager.view', 'dashboard.director.view']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('dashboard.director'));
    }

    public function test_dashboard_redirect_supports_superadmin_dashboard_permission(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('dashboard.superadmin.view', 'web');
        $user->givePermissionTo('dashboard.superadmin.view');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_services_index_and_map_require_module_permission_read(): void
    {
        $user = User::factory()->create();
        Module::query()->updateOrCreate(
            ['key' => 'service_manager'],
            ['name' => 'Service Manager', 'description' => 'Service Manager module', 'is_enabled' => true]
        );

        Permission::findOrCreate('module.service_manager.access', 'web');
        Permission::findOrCreate('module.service_manager.read', 'web');
        $user->givePermissionTo('module.service_manager.access');

        $this->actingAs($user)->get(route('services.index'))->assertForbidden();
        $this->actingAs($user)->get(route('services.map'))->assertForbidden();

        $user->givePermissionTo('module.service_manager.read');
        $this->actingAs($user)->get(route('services.index'))->assertOk();
        $this->actingAs($user)->get(route('services.map'))->assertOk();
    }

    public function test_services_toggle_requires_access_and_module_permission(): void
    {
        $user = User::factory()->create();
        Module::query()->updateOrCreate(
            ['key' => 'service_manager'],
            ['name' => 'Service Manager', 'description' => 'Service Manager module', 'is_enabled' => true]
        );
        $targetModule = Module::query()->updateOrCreate(
            ['key' => 'activities'],
            ['name' => 'Activities', 'description' => 'Activities module', 'is_enabled' => true]
        );

        Permission::findOrCreate('module.service_manager.access', 'web');

        $this->actingAs($user)
            ->patch(route('services.toggle', ['module' => $targetModule->id]))
            ->assertForbidden();

        $user->givePermissionTo('module.service_manager.access');
        $before = (bool) $targetModule->is_enabled;

        $this->actingAs($user)
            ->patch(route('services.toggle', ['module' => $targetModule->id]))
            ->assertRedirect(route('services.index'));

        $targetModule->refresh();
        $this->assertNotSame($before, (bool) $targetModule->is_enabled);
    }
}

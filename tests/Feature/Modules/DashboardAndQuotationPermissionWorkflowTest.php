<?php

namespace Tests\Feature\Modules;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardAndQuotationPermissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirect_is_permission_first(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('dashboard.director.view', 'web');
        $user->givePermissionTo('dashboard.director.view');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('dashboard.director'));
    }

    public function test_quotation_workflow_routes_are_guarded_by_specific_permissions(): void
    {
        $approveMiddleware = Route::getRoutes()->getByName('quotations.approve')?->gatherMiddleware() ?? [];
        $setPendingMiddleware = Route::getRoutes()->getByName('quotations.set-pending')?->gatherMiddleware() ?? [];
        $globalDiscountMiddleware = Route::getRoutes()->getByName('quotations.global-discount')?->gatherMiddleware() ?? [];
        $validateShowMiddleware = Route::getRoutes()->getByName('quotations.validate.show')?->gatherMiddleware() ?? [];

        $this->assertContains('permission:quotations.approve', $approveMiddleware);
        $this->assertContains('permission:quotations.set_pending', $setPendingMiddleware);
        $this->assertContains('permission:quotations.global_discount', $globalDiscountMiddleware);
        $this->assertContains('permission:quotations.validate', $validateShowMiddleware);
    }
}


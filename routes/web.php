<?php

use App\Http\Controllers\Admin\DashboardController;
// ...existing code...
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\QuotationTemplateController as AdminQuotationTemplateController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\ItineraryController as AdminItineraryController;
use App\Http\Controllers\Admin\TouristAttractionController as AdminTouristAttractionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Director\DashboardController as DirectorDashboardController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Finance\InvoiceController as FinanceInvoiceController;
use App\Http\Controllers\Operations\DashboardController as OperationsDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Sales\CustomerImportController as SalesCustomerImportController;
use App\Http\Controllers\Sales\DashboardController as SalesDashboardController;
use App\Http\Controllers\Sales\InquiryController as SalesInquiryController;
use App\Http\Controllers\Sales\QuotationController as SalesQuotationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
});

// Debug route for menu filtering with detailed output
Route::get('/debug-menu/{userId}', function ($userId) {
    $user = \App\Models\User::find($userId);
    
    if (!$user) {
        return 'User not found';
    }
    
    $composer = new \App\Http\View\SidebarComposer();
    
    // Get the filtered menu using reflection
    $reflection = new ReflectionClass($composer);
    $getMenuItems = $reflection->getMethod('getMenuItems');
    $getMenuItems->setAccessible(true);
    $filterMenuItems = $reflection->getMethod('filterMenuItems');
    $filterMenuItems->setAccessible(true);
    
    $items = $getMenuItems->invoke($composer);
    $filtered = $filterMenuItems->invoke($composer, $items, $user);
    
    $html = "<h2>User: " . $user->name . " | Roles: " . $user->getRoleNames()->join(', ') . "</h2>";
    
    $html .= "<h3>Filtered Menu Items (" . count($filtered) . " items):</h3>";
    $html .= "<table border='1' cellpadding='10'><tr><th>Title</th><th>Route</th><th>Route Exists</th></tr>";
    
    foreach ($filtered as $item) {
        $routeExists = \Route::has($item['route']);
        $routeStatus = $routeExists ? "<span style='color:green;font-weight:bold'>YES</span>" : "<span style='color:red;font-weight:bold'>NO</span>";
        $html .= "<tr><td>" . $item['title'] . "</td><td>" . $item['route'] . "</td><td>" . $routeStatus . "</td></tr>";
    }
    
    $html .= "</table>";
    
    return $html;
});

// This route acts as the gateway for role-based dashboard redirection
Route::get('/dashboard', DashboardRedirectController::class)->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('superadmin')->middleware('role:Super Admin')->name('superadmin.')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/dashboard/trend', [SuperAdminDashboardController::class, 'trend'])
            ->name('dashboard.trend');
    });

    // Shared modules (no role prefix), access controlled by module + permission
    Route::resource('customers',
        \App\Http\Controllers\Sales\CustomerController::class)
        ->except(['show'])
        ->middleware(['module:customer_management', 'permission:module.customer_management.access']);
    Route::get('customers/check-code', [\App\Http\Controllers\Sales\CustomerController::class, 'checkCode'])
        ->name('customers.check-code')
        ->middleware(['module:customer_management', 'permission:module.customer_management.access']);
    Route::get('customers/import', [SalesCustomerImportController::class, 'create'])
        ->name('customers.import')
        ->middleware(['module:customer_management', 'permission:module.customer_management.access']);
    Route::get('customers/import/template', [SalesCustomerImportController::class, 'template'])
        ->name('customers.import.template')
        ->middleware(['module:customer_management', 'permission:module.customer_management.access']);
    Route::post('customers/import/preview', [SalesCustomerImportController::class, 'preview'])
        ->name('customers.import.preview')
        ->middleware(['module:customer_management', 'permission:module.customer_management.access']);
    Route::post('customers/import', [SalesCustomerImportController::class, 'store'])
        ->name('customers.import.store')
        ->middleware(['module:customer_management', 'permission:module.customer_management.access']);

    Route::resource('inquiries', SalesInquiryController::class)
        ->middleware(['module:inquiries', 'permission:module.inquiries.access']);
    Route::post('inquiries/{inquiry}/followups', [SalesInquiryController::class, 'storeFollowUp'])
        ->name('inquiries.followups.store')
        ->middleware(['module:inquiries', 'permission:module.inquiries.access']);
    Route::patch('inquiries/followups/{followUp}', [SalesInquiryController::class, 'markFollowUpDone'])
        ->name('inquiries.followups.done')
        ->middleware(['module:inquiries', 'permission:module.inquiries.access']);
    Route::post('inquiries/{inquiry}/communications', [SalesInquiryController::class, 'storeCommunication'])
        ->name('inquiries.communications.store')
        ->middleware(['module:inquiries', 'permission:module.inquiries.access']);

    Route::resource('quotations', SalesQuotationController::class)
        ->except(['show'])
        ->middleware(['module:quotations', 'permission:module.quotations.access']);
    Route::get('quotations/{quotation}/pdf', [SalesQuotationController::class, 'generatePDF'])
        ->name('quotations.pdf')
        ->middleware(['module:quotations', 'permission:module.quotations.access']);
    Route::get('quotations/export/csv', [SalesQuotationController::class, 'exportCsv'])
        ->name('quotations.export')
        ->middleware(['module:quotations', 'permission:module.quotations.access']);
    Route::post('quotations/{quotation}/approve', [SalesQuotationController::class, 'approve'])
        ->name('quotations.approve')
        ->middleware(['role:Sales Manager|Director|Admin|Super Admin', 'module:quotations', 'permission:module.quotations.access']);
    Route::post('quotations/{quotation}/reject', [SalesQuotationController::class, 'reject'])
        ->name('quotations.reject')
        ->middleware(['role:Sales Manager|Director|Admin|Super Admin', 'module:quotations', 'permission:module.quotations.access']);

    // Admin routes group (no role prefix in route names)
    Route::group([], function () {
        Route::get('/admin-dashboard', [DashboardController::class, 'index'])
            ->name('dashboard.admin')
            ->middleware(['role:Admin|Super Admin', 'permission:dashboard.admin.view']);
        Route::get('/services', [AdminServiceController::class, 'index'])
            ->name('services.index')
            ->middleware('permission:module.service_manager.access');
        Route::patch('/services/{module}/toggle', [AdminServiceController::class, 'toggle'])
            ->name('services.toggle')
            ->middleware('permission:module.service_manager.access');
        Route::resource('users', AdminUserController::class)
            ->except(['show'])
            ->middleware(['module:user_manager', 'permission:module.user_manager.access']);
        Route::resource('roles', AdminRoleController::class)
            ->except(['show'])
            ->middleware(['module:role_manager', 'permission:module.role_manager.access']);
        Route::resource('quotation-templates', AdminQuotationTemplateController::class)
            ->except(['show'])
            ->middleware(['module:quotation_templates', 'permission:module.quotation_templates.access']);
        Route::resource('itineraries', AdminItineraryController::class)
            ->middleware(['module:itineraries', 'permission:module.itineraries.access']);
        Route::resource('tourist-attractions', AdminTouristAttractionController::class)
            ->except(['show'])
            ->middleware(['module:tourist_attractions', 'permission:module.tourist_attractions.access']);
    });

    // Shared module routes for vendor/activity (no URL prefix)
    Route::group([], function () {
        Route::resource('vendors', AdminVendorController::class)
            ->except(['show'])
            ->middleware(['module:vendor_management', 'permission:module.vendor_management.access']);
        Route::resource('activities', AdminActivityController::class)
            ->except(['show'])
            ->middleware(['module:activities', 'permission:module.activities.access']);

    });

    // ...existing code...

    // ----------------------------------------------------------------------------------------------------------
    // Sales
    // ----------------------------------------------------------------------------------------------------------
    Route::group([], function () {
        Route::get('/sales-dashboard', [SalesDashboardController::class, 'index'])
            ->name('dashboard.sales')
            ->middleware(['role:Sales Manager|Sales Agent', 'permission:dashboard.sales.view']);
    });
    // ----------------------------------------------------------------------------------------------------------
    // Finance
    // ----------------------------------------------------------------------------------------------------------
    Route::group([], function () {
        Route::get('/finance-dashboard', [FinanceDashboardController::class, 'index'])
            ->name('dashboard.finance')
            ->middleware(['role:Finance', 'permission:dashboard.finance.view']);
        Route::get('invoices', [FinanceInvoiceController::class, 'index'])
            ->name('invoices.index')
            ->middleware(['module:invoices', 'permission:module.invoices.access']);
        Route::get('invoices/{invoice}', [FinanceInvoiceController::class, 'show'])
            ->name('invoices.show')
            ->middleware(['module:invoices', 'permission:module.invoices.access']);
    });
    // ----------------------------------------------------------------------------------------------------------
    // Director
    // ----------------------------------------------------------------------------------------------------------
    Route::group([], function () {
        Route::get('/director-dashboard', [DirectorDashboardController::class, 'index'])
            ->name('dashboard.director')
            ->middleware(['role:Director', 'permission:dashboard.director.view']);
    });
    // ----------------------------------------------------------------------------------------------------------
    // Operations
    // ----------------------------------------------------------------------------------------------------------
    Route::group([], function () {
        Route::get('/operations-dashboard', [OperationsDashboardController::class, 'index'])
            ->name('dashboard.operations')
            ->middleware(['role:Operations|Sales Manager', 'permission:dashboard.operations.view']);
        Route::resource('bookings', \App\Http\Controllers\BookingController::class)
            ->middleware(['module:bookings', 'permission:module.bookings.access']);
        Route::get('bookings/export/csv', [\App\Http\Controllers\BookingController::class, 'exportCsv'])
            ->name('bookings.export')
            ->middleware(['module:bookings', 'permission:module.bookings.access']);
    });
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\QuotationController as AdminQuotationController;
use App\Http\Controllers\Admin\QuotationTemplateController as AdminQuotationTemplateController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\ServiceItemController as AdminServiceItemController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Director\DashboardController as DirectorDashboardController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Operations\DashboardController as OperationsDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sales\CustomerImportController as SalesCustomerImportController;
use App\Http\Controllers\Sales\DashboardController as SalesDashboardController;
use App\Http\Controllers\Sales\InquiryController as SalesInquiryController;
use App\Http\Controllers\Sales\QuotationController as SalesQuotationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

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

    // Admin routes group
    Route::prefix('admin')->middleware('role:Admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('permission:module.admin_dashboard.access');
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
    });

    // Admin + Operations + Sales roles can access Vendors & Services
    Route::prefix('admin')->middleware('role:Admin|Operations|Sales Manager|Sales Agent')->name('admin.')->group(function () {
        Route::resource('vendors', AdminVendorController::class)
            ->except(['show'])
            ->middleware(['module:vendor_management', 'permission:module.vendor_management.access']);

        Route::prefix('services')->name('services.items.')->group(function () {
            Route::prefix('accommodations')->middleware(['module:services', 'module:services_accommodations', 'permission:module.services.access', 'permission:module.services_accommodations.access'])->group(function () {
                Route::get('/', [AdminServiceItemController::class, 'index'])->name('accommodations.index')->defaults('serviceType', 'accommodations');
                Route::get('/create', [AdminServiceItemController::class, 'create'])->name('accommodations.create')->defaults('serviceType', 'accommodations');
                Route::post('/', [AdminServiceItemController::class, 'store'])->name('accommodations.store')->defaults('serviceType', 'accommodations');
                Route::get('/{service}/edit', [AdminServiceItemController::class, 'edit'])->name('accommodations.edit')->defaults('serviceType', 'accommodations');
                Route::put('/{service}', [AdminServiceItemController::class, 'update'])->name('accommodations.update')->defaults('serviceType', 'accommodations');
                Route::delete('/{service}', [AdminServiceItemController::class, 'destroy'])->name('accommodations.destroy')->defaults('serviceType', 'accommodations');
            });

            Route::prefix('transports')->middleware(['module:services', 'module:services_transports', 'permission:module.services.access', 'permission:module.services_transports.access'])->group(function () {
                Route::get('/', [AdminServiceItemController::class, 'index'])->name('transports.index')->defaults('serviceType', 'transports');
                Route::get('/create', [AdminServiceItemController::class, 'create'])->name('transports.create')->defaults('serviceType', 'transports');
                Route::post('/', [AdminServiceItemController::class, 'store'])->name('transports.store')->defaults('serviceType', 'transports');
                Route::get('/{service}/edit', [AdminServiceItemController::class, 'edit'])->name('transports.edit')->defaults('serviceType', 'transports');
                Route::put('/{service}', [AdminServiceItemController::class, 'update'])->name('transports.update')->defaults('serviceType', 'transports');
                Route::delete('/{service}', [AdminServiceItemController::class, 'destroy'])->name('transports.destroy')->defaults('serviceType', 'transports');
            });

            Route::prefix('guides')->middleware(['module:services', 'module:services_guides', 'permission:module.services.access', 'permission:module.services_guides.access'])->group(function () {
                Route::get('/', [AdminServiceItemController::class, 'index'])->name('guides.index')->defaults('serviceType', 'guides');
                Route::get('/create', [AdminServiceItemController::class, 'create'])->name('guides.create')->defaults('serviceType', 'guides');
                Route::post('/', [AdminServiceItemController::class, 'store'])->name('guides.store')->defaults('serviceType', 'guides');
                Route::get('/{service}/edit', [AdminServiceItemController::class, 'edit'])->name('guides.edit')->defaults('serviceType', 'guides');
                Route::put('/{service}', [AdminServiceItemController::class, 'update'])->name('guides.update')->defaults('serviceType', 'guides');
                Route::delete('/{service}', [AdminServiceItemController::class, 'destroy'])->name('guides.destroy')->defaults('serviceType', 'guides');
            });

            Route::prefix('attractions')->middleware(['module:services', 'module:services_attractions', 'permission:module.services.access', 'permission:module.services_attractions.access'])->group(function () {
                Route::get('/', [AdminServiceItemController::class, 'index'])->name('attractions.index')->defaults('serviceType', 'attractions');
                Route::get('/create', [AdminServiceItemController::class, 'create'])->name('attractions.create')->defaults('serviceType', 'attractions');
                Route::post('/', [AdminServiceItemController::class, 'store'])->name('attractions.store')->defaults('serviceType', 'attractions');
                Route::get('/{service}/edit', [AdminServiceItemController::class, 'edit'])->name('attractions.edit')->defaults('serviceType', 'attractions');
                Route::put('/{service}', [AdminServiceItemController::class, 'update'])->name('attractions.update')->defaults('serviceType', 'attractions');
                Route::delete('/{service}', [AdminServiceItemController::class, 'destroy'])->name('attractions.destroy')->defaults('serviceType', 'attractions');
            });

            Route::prefix('travel-activities')->middleware(['module:services', 'module:services_travel_activities', 'permission:module.services.access', 'permission:module.services_travel_activities.access'])->group(function () {
                Route::get('/', [AdminServiceItemController::class, 'index'])->name('travel-activities.index')->defaults('serviceType', 'travel_activities');
                Route::get('/create', [AdminServiceItemController::class, 'create'])->name('travel-activities.create')->defaults('serviceType', 'travel_activities');
                Route::post('/', [AdminServiceItemController::class, 'store'])->name('travel-activities.store')->defaults('serviceType', 'travel_activities');
                Route::get('/{service}/edit', [AdminServiceItemController::class, 'edit'])->name('travel-activities.edit')->defaults('serviceType', 'travel_activities');
                Route::put('/{service}', [AdminServiceItemController::class, 'update'])->name('travel-activities.update')->defaults('serviceType', 'travel_activities');
                Route::delete('/{service}', [AdminServiceItemController::class, 'destroy'])->name('travel-activities.destroy')->defaults('serviceType', 'travel_activities');
            });
        });
    });

    // Admin + Sales Manager can access Sales Target
    Route::prefix('admin')->middleware('role:Admin|Sales Manager')->name('admin.')->group(function () {
        Route::resource('salestargets', \App\Http\Controllers\SalesTargetController::class)
            ->except(['show'])
            ->middleware(['module:sales_target', 'permission:module.sales_target.access']);
    });

    Route::prefix('admin')->middleware('role:Admin|Sales Manager|Director')->name('admin.')->group(function () {
        Route::resource('promotions', AdminPromotionController::class)->except(['show'])->middleware(['module:promotions', 'permission:module.promotions.access']);
        Route::resource('quotations', AdminQuotationController::class)
            ->except(['show'])
            ->middleware(['module:quotations', 'permission:module.quotations.access']);
        Route::get('quotations/{quotation}/pdf', [AdminQuotationController::class, 'generatePDF'])
            ->name('quotations.pdf')
            ->middleware(['module:quotations', 'permission:module.quotations.access']);
        Route::get('quotations/export/csv', [AdminQuotationController::class, 'exportCsv'])
            ->name('quotations.export')
            ->middleware(['module:quotations', 'permission:module.quotations.access']);
        Route::post('quotations/{quotation}/approve', [AdminQuotationController::class, 'approve'])
            ->name('quotations.approve')
            ->middleware(['role:Admin', 'module:quotations', 'permission:module.quotations.access']);
        Route::post('quotations/{quotation}/reject', [AdminQuotationController::class, 'reject'])
            ->name('quotations.reject')
            ->middleware(['role:Admin', 'module:quotations', 'permission:module.quotations.access']);
    });
    // ----------------------------------------------------------------------------------------------------------
    // Sales
    // ----------------------------------------------------------------------------------------------------------
    Route::prefix('sales')->middleware('role:Sales Manager|Sales Agent')->name('sales.')->group(function () {
        Route::get('/dashboard', [SalesDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('permission:module.sales_dashboard.access');
        Route::resource('customers',
            \App\Http\Controllers\Sales\CustomerController::class)
            ->except(['show'])
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
            ->except(['show'])
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
            ->middleware(['role:Sales Manager|Director', 'module:quotations', 'permission:module.quotations.access']);
        Route::post('quotations/{quotation}/reject', [SalesQuotationController::class, 'reject'])
            ->name('quotations.reject')
            ->middleware(['role:Sales Manager|Director', 'module:quotations', 'permission:module.quotations.access']);
    });
    // ----------------------------------------------------------------------------------------------------------
    // Finance
    // ----------------------------------------------------------------------------------------------------------
    Route::prefix('finance')->middleware('role:Finance')->name('finance.')->group(function () {
        Route::get('/dashboard', [FinanceDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('permission:module.finance_dashboard.access');
    });
    // ----------------------------------------------------------------------------------------------------------
    // Director
    // ----------------------------------------------------------------------------------------------------------
    Route::prefix('director')->middleware('role:Director')->name('director.')->group(function () {
        Route::get('/dashboard', [DirectorDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware(['module:director_dashboard', 'permission:module.director_dashboard.access']);
    });
    // ----------------------------------------------------------------------------------------------------------
    // Operations
    // ----------------------------------------------------------------------------------------------------------
    Route::prefix('operations')->middleware('role:Operations|Sales Manager')->name('operations.')->group(function () {
        Route::get('/dashboard', [OperationsDashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('permission:module.operations_dashboard.access');
        Route::resource('bookings', \App\Http\Controllers\BookingController::class)
            ->middleware(['module:bookings', 'permission:module.bookings.access']);
        Route::get('bookings/export/csv', [\App\Http\Controllers\BookingController::class, 'exportCsv'])
            ->name('bookings.export')
            ->middleware(['module:bookings', 'permission:module.bookings.access']);
    });
});

require __DIR__.'/auth.php';

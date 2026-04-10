<?php

use App\Http\Controllers\Administrator\DashboardController as AdministratorDashboardController;
// ...existing code...
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\AirportController as AdminAirportController;
use App\Http\Controllers\Admin\CurrencyController as AdminCurrencyController;
use App\Http\Controllers\Admin\DestinationController as AdminDestinationController;
use App\Http\Controllers\Admin\FoodBeverageController as AdminFoodBeverageController;
use App\Http\Controllers\Admin\HotelController as AdminHotelController;
use App\Http\Controllers\Admin\TransportController as AdminTransportController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\ServiceMapController as AdminServiceMapController;
use App\Http\Controllers\Admin\ItineraryController as AdminItineraryController;
use App\Http\Controllers\Admin\LocationResolverController as AdminLocationResolverController;
use App\Http\Controllers\Admin\TouristAttractionController as AdminTouristAttractionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\Director\DashboardController as DirectorDashboardController;
use App\Http\Controllers\Editor\DashboardController as EditorDashboardController;
use App\Http\Controllers\Director\CompanySettingController as DirectorCompanySettingController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Finance\InvoiceController as FinanceInvoiceController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Marketing\DashboardController as MarketingDashboardController;
use App\Http\Controllers\Reservation\DashboardController as ReservationDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\AccessMatrixController as SuperAdminAccessMatrixController;
use App\Http\Controllers\Sales\CustomerImportController as SalesCustomerImportController;
use App\Http\Controllers\Sales\InquiryController as SalesInquiryController;
use App\Http\Controllers\Sales\QuotationController as SalesQuotationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
});

Route::post('/currency', [CurrencyController::class, 'set'])
    ->name('currency.set')
    ->middleware('auth');

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
    Route::get('location/resolve-google-map', [AdminLocationResolverController::class, 'resolve'])
        ->name('location.resolve-google-map');

    Route::prefix('superadmin')->middleware('role:Super Admin')->name('superadmin.')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/dashboard/trend', [SuperAdminDashboardController::class, 'trend'])
            ->name('dashboard.trend');
        Route::get('/access-matrix', [SuperAdminAccessMatrixController::class, 'index'])
            ->name('access-matrix');
    });

    Route::get('company-settings', [DirectorCompanySettingController::class, 'edit'])
        ->name('company-settings.edit')
        ->middleware(['permission:company_settings.manage']);
    Route::patch('company-settings', [DirectorCompanySettingController::class, 'update'])
        ->name('company-settings.update')
        ->middleware(['permission:company_settings.manage']);

    // Shared modules (no role prefix), access controlled by module + permission
    Route::resource('customers',
        \App\Http\Controllers\Sales\CustomerController::class)
        ->except(['show'])
        ->middleware([
            'module:customer_management',
            'permission:module.customer_management.access',
            'module.permission:customer_management',
        ]);
    Route::patch('customers/{customer}/toggle-status', [\App\Http\Controllers\Sales\CustomerController::class, 'toggleStatus'])
        ->name('customers.toggle-status')
        ->middleware([
            'module:customer_management',
            'permission:module.customer_management.access',
            'module.permission:customer_management',
        ]);
    Route::get('customers/check-code', [\App\Http\Controllers\Sales\CustomerController::class, 'checkCode'])
        ->name('customers.check-code')
        ->middleware([
            'module:customer_management',
            'permission:module.customer_management.access',
            'module.permission:customer_management',
        ]);
    Route::get('customers/import', [SalesCustomerImportController::class, 'create'])
        ->name('customers.import')
        ->middleware([
            'module:customer_management',
            'permission:module.customer_management.access',
            'module.permission:customer_management',
        ]);
    Route::get('customers/import/template', [SalesCustomerImportController::class, 'template'])
        ->name('customers.import.template')
        ->middleware([
            'module:customer_management',
            'permission:module.customer_management.access',
            'module.permission:customer_management',
        ]);
    Route::post('customers/import/preview', [SalesCustomerImportController::class, 'preview'])
        ->name('customers.import.preview')
        ->middleware([
            'module:customer_management',
            'permission:module.customer_management.access',
            'module.permission:customer_management',
        ]);
    Route::post('customers/import', [SalesCustomerImportController::class, 'store'])
        ->name('customers.import.store')
        ->middleware([
            'module:customer_management',
            'permission:module.customer_management.access',
            'module.permission:customer_management',
        ]);

    Route::resource('inquiries', SalesInquiryController::class)
        ->middleware([
            'module:inquiries',
            'permission:module.inquiries.access',
            'module.permission:inquiries',
        ])->except(['destroy']);
    Route::post('inquiries/{inquiry}/followups', [SalesInquiryController::class, 'storeFollowUp'])
        ->name('inquiries.followups.store')
        ->middleware([
            'module:inquiries',
            'permission:module.inquiries.access',
            'module.permission:inquiries',
        ]);
    Route::patch('inquiries/followups/{followUp}', [SalesInquiryController::class, 'markFollowUpDone'])
        ->name('inquiries.followups.done')
        ->middleware([
            'module:inquiries',
            'permission:module.inquiries.access',
            'module.permission:inquiries',
        ]);
    Route::post('inquiries/{inquiry}/communications', [SalesInquiryController::class, 'storeCommunication'])
        ->name('inquiries.communications.store')
        ->middleware([
            'module:inquiries',
            'permission:module.inquiries.access',
            'module.permission:inquiries',
        ]);

    Route::resource('quotations', SalesQuotationController::class)
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::patch('quotations/{quotation}/toggle-status', [SalesQuotationController::class, 'toggleStatus'])
        ->name('quotations.toggle-status')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::get('quotations/itinerary-items/{itinerary}', [SalesQuotationController::class, 'itineraryItems'])
        ->name('quotations.itinerary-items')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module:itineraries',
            'permission:module.itineraries.access',
            'module.permission:itineraries',
        ]);
    Route::get('quotations/{quotation}/pdf', [SalesQuotationController::class, 'generatePDF'])
        ->name('quotations.pdf')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::get('quotations/export/csv', [SalesQuotationController::class, 'exportCsv'])
        ->name('quotations.export')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::post('quotations/{quotation}/approve', [SalesQuotationController::class, 'approve'])
        ->name('quotations.approve')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::post('quotations/{quotation}/reject', [SalesQuotationController::class, 'reject'])
        ->name('quotations.reject')
        ->middleware(['module:quotations', 'permission:quotations.reject']);
    Route::post('quotations/{quotation}/set-pending', [SalesQuotationController::class, 'setPending'])
        ->name('quotations.set-pending')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::post('quotations/{quotation}/set-final', [SalesQuotationController::class, 'setFinal'])
        ->name('quotations.set-final')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::patch('quotations/{quotation}/global-discount', [SalesQuotationController::class, 'updateGlobalDiscount'])
        ->name('quotations.global-discount')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::post('quotations/{quotation}/comments', [SalesQuotationController::class, 'storeComment'])
        ->name('quotations.comments.store')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::get('quotations/approval-notifications/poll', [SalesQuotationController::class, 'approvalNotifications'])
        ->name('quotations.approval-notifications.poll')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::put('quotations/{quotation}/comments/{comment}', [SalesQuotationController::class, 'updateComment'])
        ->name('quotations.comments.update')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);
    Route::delete('quotations/{quotation}/comments/{comment}', [SalesQuotationController::class, 'destroyComment'])
        ->name('quotations.comments.destroy')
        ->middleware([
            'module:quotations',
            'permission:module.quotations.access',
            'module.permission:quotations',
        ]);

    // Admin routes group (no role prefix in route names)
    Route::group([], function () {
        Route::get('/administrator-dashboard', [AdministratorDashboardController::class, 'index'])
            ->name('dashboard.administrator')
            ->middleware(['permission:dashboard.administrator.view']);
        Route::get('/services', [AdminServiceController::class, 'index'])
            ->name('services.index')
            ->middleware('permission:module.service_manager.access');
        Route::get('/services/map', [AdminServiceMapController::class, 'index'])
            ->name('services.map')
            ->middleware('permission:module.service_manager.access');
        Route::patch('/services/{module}/toggle', [AdminServiceController::class, 'toggle'])
            ->name('services.toggle')
            ->middleware('permission:module.service_manager.access');
        Route::resource('users', AdminUserController::class)
            ->except(['show'])
            ->middleware([
                'module:user_manager',
                'permission:module.user_manager.access',
                'module.permission:user_manager',
                'block.superadmin.target:user',
            ]);
        Route::resource('roles', AdminRoleController::class)
            ->except(['show'])
            ->middleware([
                'module:role_manager',
                'permission:module.role_manager.access',
                'module.permission:role_manager',
            ]);
        Route::get('itineraries/destination-suggestions', [AdminItineraryController::class, 'destinationSuggestions'])
            ->name('itineraries.destination-suggestions')
            ->middleware([
                'module:itineraries',
                'permission:module.itineraries.access',
                'module.permission:itineraries',
            ]);
        Route::resource('itineraries', AdminItineraryController::class)->except(['destroy'])
            ->middleware([
                'module:itineraries',
                'permission:module.itineraries.access',
                'module.permission:itineraries',
            ]);
        Route::post('itineraries/{itinerary}/duplicate', [AdminItineraryController::class, 'duplicate'])
            ->name('itineraries.duplicate')
            ->middleware([
                'module:itineraries',
                'permission:module.itineraries.access',
                'module.permission:itineraries',
            ]);
        Route::get('itineraries/{itinerary}/pdf', [AdminItineraryController::class, 'generatePdf'])
            ->name('itineraries.pdf')
            ->middleware([
                'module:itineraries',
                'permission:module.itineraries.access',
                'module.permission:itineraries',
            ]);
        Route::resource('tourist-attractions', AdminTouristAttractionController::class)
            ->except(['show'])
            ->middleware([
                'module:tourist_attractions',
                'permission:module.tourist_attractions.access',
                'module.permission:tourist_attractions',
            ]);
        Route::patch('tourist-attractions/{tourist_attraction}/toggle-status', [AdminTouristAttractionController::class, 'toggleStatus'])
            ->name('tourist-attractions.toggle-status')
            ->middleware([
                'module:tourist_attractions',
                'permission:module.tourist_attractions.access',
                'module.permission:tourist_attractions',
            ]);
        Route::post('tourist-attractions/{tourist_attraction}/gallery-images/remove', [AdminTouristAttractionController::class, 'removeGalleryImage'])
            ->name('tourist-attractions.gallery-images.remove')
            ->middleware([
                'module:tourist_attractions',
                'permission:module.tourist_attractions.access',
                'module.permission:tourist_attractions',
            ]);
        Route::resource('hotels', AdminHotelController::class)
            ->middleware([
                'module:hotels',
                'permission:module.hotels.access',
                'module.permission:hotels',
            ]);
        Route::patch('hotels/{hotel}/info', [AdminHotelController::class, 'updateInfo'])
            ->name('hotels.update-info')
            ->middleware([
                'module:hotels',
                'permission:module.hotels.access',
                'module.permission:hotels',
            ]);
        Route::patch('hotels/{hotel}/rooms', [AdminHotelController::class, 'updateRooms'])
            ->name('hotels.update-rooms')
            ->middleware([
                'module:hotels',
                'permission:module.hotels.access',
                'module.permission:hotels',
            ]);
        Route::patch('hotels/{hotel}/prices', [AdminHotelController::class, 'updatePrices'])
            ->name('hotels.update-prices')
            ->middleware([
                'module:hotels',
                'permission:module.hotels.access',
                'module.permission:hotels',
            ]);
        Route::patch('hotels/{hotel}/toggle-status', [AdminHotelController::class, 'toggleStatus'])
            ->name('hotels.toggle-status')
            ->middleware([
                'module:hotels',
                'permission:module.hotels.access',
                'module.permission:hotels',
            ]);
        Route::resource('airports', AdminAirportController::class)
            ->middleware([
                'module:airports',
                'permission:module.airports.access',
                'module.permission:airports',
            ]);
        Route::patch('airports/{airport}/toggle-status', [AdminAirportController::class, 'toggleStatus'])
            ->name('airports.toggle-status')
            ->middleware([
                'module:airports',
                'permission:module.airports.access',
                'module.permission:airports',
            ]);
        Route::resource('currencies', AdminCurrencyController::class)
            ->middleware([
                'module:currencies',
                'permission:module.currencies.access',
                'module.permission:currencies',
            ]);
        Route::post('currencies/bulk-update', [AdminCurrencyController::class, 'bulkUpdate'])
            ->name('currencies.bulk-update')
            ->middleware([
                'module:currencies',
                'permission:module.currencies.access',
                'module.permission:currencies',
            ]);
        Route::resource('destinations', AdminDestinationController::class)
            ->middleware([
                'module:destinations',
                'permission:module.destinations.access',
                'module.permission:destinations',
            ]);
        Route::patch('destinations/{destination}/toggle-status', [AdminDestinationController::class, 'toggleStatus'])
            ->name('destinations.toggle-status')
            ->middleware([
                'module:destinations',
                'permission:module.destinations.access',
                'module.permission:destinations',
            ]);
        Route::resource('transports', AdminTransportController::class)
            ->middleware([
                'module:transports',
                'permission:module.transports.access',
                'module.permission:transports',
            ]);
        Route::patch('transports/{transport}/toggle-status', [AdminTransportController::class, 'toggleStatus'])
            ->name('transports.toggle-status')
            ->middleware([
                'module:transports',
                'permission:module.transports.access',
                'module.permission:transports',
            ]);
        Route::post('transports/{transport}/gallery-images/remove', [AdminTransportController::class, 'removeGalleryImage'])
            ->name('transports.gallery-images.remove')
            ->middleware([
                'module:transports',
                'permission:module.transports.access',
                'module.permission:transports',
            ]);
    });

    // Shared module routes for vendor/activity (no URL prefix)
    Route::group([], function () {
        Route::resource('vendors', AdminVendorController::class)
            ->except(['show'])
            ->middleware([
                'module:vendor_management',
                'permission:module.vendor_management.access',
                'module.permission:vendor_management',
            ]);
        Route::patch('vendors/{vendor}/toggle-status', [AdminVendorController::class, 'toggleStatus'])
            ->name('vendors.toggle-status')
            ->middleware([
                'module:vendor_management',
                'permission:module.vendor_management.access',
                'module.permission:vendor_management',
            ]);
        Route::resource('activities', AdminActivityController::class)
            ->except(['show'])
            ->middleware([
                'module:activities',
                'permission:module.activities.access',
                'module.permission:activities',
            ]);
        Route::get('activities/{activity}', [AdminActivityController::class, 'show'])
            ->name('activities.show')
            ->middleware([
                'module:activities',
                'permission:module.activities.access',
                'module.permission:activities',
            ]);
        Route::patch('activities/{activity}/toggle-status', [AdminActivityController::class, 'toggleStatus'])
            ->name('activities.toggle-status')
            ->middleware([
                'module:activities',
                'permission:module.activities.access',
                'module.permission:activities',
            ]);
    Route::post('activities/{activity}/gallery-images/remove', [AdminActivityController::class, 'removeGalleryImage'])
        ->name('activities.gallery-images.remove')
        ->middleware([
            'module:activities',
            'permission:module.activities.access',
            'module.permission:activities',
        ]);
        Route::resource('food-beverages', AdminFoodBeverageController::class)
            ->except(['show'])
            ->middleware([
                'module:food_beverages',
                'permission:module.food_beverages.access',
                'module.permission:food_beverages',
            ]);
        Route::patch('food-beverages/{food_beverage}/toggle-status', [AdminFoodBeverageController::class, 'toggleStatus'])
            ->name('food-beverages.toggle-status')
            ->middleware([
                'module:food_beverages',
                'permission:module.food_beverages.access',
                'module.permission:food_beverages',
            ]);
    Route::post('food-beverages/{food_beverage}/gallery-images/remove', [AdminFoodBeverageController::class, 'removeGalleryImage'])
        ->name('food-beverages.gallery-images.remove')
        ->middleware([
            'module:food_beverages',
            'permission:module.food_beverages.access',
            'module.permission:food_beverages',
        ]);

    });

    // ...existing code...

    // ----------------------------------------------------------------------------------------------------------
    // Manager & Marketing
    // ----------------------------------------------------------------------------------------------------------
    Route::group([], function () {
        Route::get('/manager-dashboard', [ManagerDashboardController::class, 'index'])
            ->name('dashboard.manager')
            ->middleware(['permission:dashboard.manager.view']);
        Route::get('/marketing-dashboard', [MarketingDashboardController::class, 'index'])
            ->name('dashboard.marketing')
            ->middleware(['permission:dashboard.marketing.view']);
    });
    // ----------------------------------------------------------------------------------------------------------
    // Finance
    // ----------------------------------------------------------------------------------------------------------
    Route::group([], function () {
        Route::get('/finance-dashboard', [FinanceDashboardController::class, 'index'])
            ->name('dashboard.finance')
            ->middleware(['permission:dashboard.finance.view']);
        Route::get('invoices', [FinanceInvoiceController::class, 'index'])
            ->name('invoices.index')
            ->middleware([
                'module:invoices',
                'permission:module.invoices.access',
                'module.permission:invoices',
            ]);
        Route::get('invoices/{invoice}', [FinanceInvoiceController::class, 'show'])
            ->name('invoices.show')
            ->middleware([
                'module:invoices',
                'permission:module.invoices.access',
                'module.permission:invoices',
            ]);
    });
    // ----------------------------------------------------------------------------------------------------------
    // Director
    // ----------------------------------------------------------------------------------------------------------
    Route::group([], function () {
        Route::get('/director-dashboard', [DirectorDashboardController::class, 'index'])
            ->name('dashboard.director')
            ->middleware(['permission:dashboard.director.view']);
    });
    // ----------------------------------------------------------------------------------------------------------
    // Reservation
    // ----------------------------------------------------------------------------------------------------------
    Route::group([], function () {
        Route::get('/reservation-dashboard', [ReservationDashboardController::class, 'index'])
            ->name('dashboard.reservation')
            ->middleware(['permission:dashboard.reservation.view']);
        Route::resource('bookings', \App\Http\Controllers\BookingController::class)
            ->middleware([
                'module:bookings',
                'permission:module.bookings.access',
                'module.permission:bookings',
            ]);
        Route::get('bookings/export/csv', [\App\Http\Controllers\BookingController::class, 'exportCsv'])
            ->name('bookings.export')
            ->middleware(['module:bookings', 'permission:module.bookings.access']);
    });

    // ----------------------------------------------------------------------------------------------------------
    // Editor
    // ----------------------------------------------------------------------------------------------------------
    Route::group([], function () {
        Route::get('/editor-dashboard', [EditorDashboardController::class, 'index'])
            ->name('dashboard.editor')
            ->middleware(['permission:dashboard.editor.view']);
    });
});

require __DIR__.'/auth.php';

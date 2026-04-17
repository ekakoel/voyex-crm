<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Policies\BookingPolicy;
use App\Policies\InquiryPolicy;
use App\Policies\ItineraryPolicy;
use App\Policies\QuotationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Inquiry::class => InquiryPolicy::class,
        Itinerary::class => ItineraryPolicy::class,
        Quotation::class => QuotationPolicy::class,
        Booking::class => BookingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function ($user) {
            return $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin() ? true : null;
        });
    }
}

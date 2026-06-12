<?php

namespace Tests\Unit\Services;

use App\Models\BookingItem;
use App\Services\BookingVoucherService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingVoucherServiceGuardTest extends TestCase
{
    public function test_voucher_generation_requires_vendor_confirmed(): void
    {
        $service = new BookingVoucherService();
        $bookingItem = new BookingItem();
        $bookingItem->vendor_confirmation_status = BookingItem::VENDOR_CONFIRMATION_PENDING;

        $thrown = false;
        try {
            $service->assertVendorConfirmed($bookingItem);
        } catch (ValidationException $e) {
            $thrown = true;
        }

        $this->assertTrue($thrown, 'Expected ValidationException when vendor is not confirmed.');
    }
}

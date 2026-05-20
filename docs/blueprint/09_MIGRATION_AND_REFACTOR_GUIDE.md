# MIGRATION AND REFACTOR GUIDE

## 1. Jangan Edit Migration Lama Jika Project Sudah Pernah Deploy

Jika project sudah pernah jalan di server/staging/production, jangan mengubah migration lama. Buat migration baru.

Benar:

```bash
php artisan make:migration alter_inquiries_status_to_string
```

Salah:

```text
Mengedit migration create_inquiries_table lama
```

## 2. Status Column Lebih Aman Menggunakan String

Untuk workflow yang akan berkembang, gunakan:

```php
$table->string('status')->default('new_request')->index();
```

Lebih fleksibel daripada enum database karena status travel agent sering bertambah.

## 3. Mapping Status Lama ke Baru

Contoh:

```php
$newStatus = match ($oldStatus) {
    'new' => 'new_request',
    'follow_up' => 'contacted',
    'quoted' => 'quotation_sent',
    'converted' => 'converted_to_booking',
    'closed' => 'lost',
    default => 'new_request',
};
```

Catatan: mapping `closed` harus diaudit manual karena bisa berarti lost, cancelled, expired, atau converted.

## 4. Gunakan Enum PHP / Config

Contoh:

```php
namespace App\Enums;

enum InquiryStatus: string
{
    case NewRequest = 'new_request';
    case Registered = 'registered';
    case Assigned = 'assigned';
    case Contacted = 'contacted';
    case WaitingCustomer = 'waiting_customer';
    case Qualified = 'qualified';
    case Unqualified = 'unqualified';
    case ItineraryInProgress = 'itinerary_in_progress';
    case QuotationInProgress = 'quotation_in_progress';
    case QuotationSent = 'quotation_sent';
    case UnderNegotiation = 'under_negotiation';
    case Accepted = 'accepted';
    case ConvertedToBooking = 'converted_to_booking';
    case Lost = 'lost';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function isFinal(): bool
    {
        return in_array($this, [
            self::ConvertedToBooking,
            self::Lost,
            self::Cancelled,
            self::Expired,
            self::Unqualified,
        ], true);
    }
}
```

## 5. Conversion Harus Pakai Transaction

Contoh flow quotation accepted ke booking:

```php
DB::transaction(function () use ($quotation, $user) {
    if ($quotation->status !== 'accepted') {
        throw new DomainException('Only accepted quotation can be converted to booking.');
    }

    $booking = Booking::create([...]);

    foreach ($quotation->items as $item) {
        BookingItem::create([...]);
    }

    $quotation->update(['status' => 'converted']);
    $quotation->inquiry?->update(['status' => 'converted_to_booking']);
});
```

## 6. Controller Jangan Menampung Semua Logic

Gunakan service layer:

```text
app/Services/Inquiry/
app/Services/Itinerary/
app/Services/Quotation/
app/Services/Booking/
app/Services/Finance/
app/Services/Operation/
app/Services/Settlement/
```

## 7. Locking Rule

Tambahkan method di model/service:

```php
public function canBeEdited(): bool
{
    return ! in_array($this->status, ['accepted', 'converted', 'closed'], true);
}
```

## 8. Testing Minimal Setelah Refactor

Setelah setiap phase:

```bash
php artisan migrate
php artisan optimize:clear
php artisan route:list
php artisan test
```

Manual test:

- Create customer
- Create inquiry
- Create itinerary
- Generate quotation
- Validate quotation
- Accept quotation
- Convert booking
- Generate invoice
- Confirm payment
- Close booking

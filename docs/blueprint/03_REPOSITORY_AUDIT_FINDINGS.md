# PRELIMINARY REPOSITORY AUDIT FINDINGS

Repository: `https://github.com/ekakoel/voyex-crm.git`  
Default branch: `main`

> Catatan: ini adalah audit awal berdasarkan akses baca GitHub terhadap file penting, bukan full runtime audit di local/server. Codex tetap harus melakukan audit langsung di project sebelum mengubah code.

## 1. Modul yang Terlihat Sudah Ada

Dari routes dan hasil pencarian repository, project sudah memiliki indikasi modul berikut:

- Customer Management
- Inquiry Management
- Itinerary Management
- Quotation Management
- Quotation Validation
- Booking Management
- Finance Invoice
- Vendor / Service Catalog
- Hotel
- Transport
- Food & Beverage
- Tourist Attraction
- Airport
- Currency
- Role / Permission / Module access
- Dashboard per role
- Activity / audit log

## 2. Routes yang Sudah Mengarah ke Flow Utama

Route utama yang ditemukan:

- `customers` resource
- `inquiries` resource
- `quotations` resource
- `quotations/{quotation}/validate`
- `quotations/{quotation}/validate/finalize`
- `itineraries` resource
- `itineraries/{itinerary}/duplicate`
- `itineraries/{itinerary}/pdf`
- module middleware dan permission middleware

Artinya sistem sudah punya pondasi workflow yang cukup kuat. Fokus berikutnya bukan rebuild, tetapi penyelarasan rule dan lifecycle.

## 3. Temuan Inquiry

### Migration awal inquiry

Migration awal `create_inquiries_table` masih menggunakan status sederhana:

```php
['new','follow_up','quoted','converted','closed']
```

Status ini belum cukup untuk flow travel agent yang lebih lengkap.

### Model Inquiry

Model `Inquiry.php` memiliki `STATUS_OPTIONS`:

```php
[
    'draft',
    'processed',
    'pending',
    'approved',
    'rejected',
    'final',
]
```

Ini tidak sama dengan migration awal inquiry. Ini perlu diaudit karena potensi inconsistency antara database, model, form, filter, dashboard, dan command sync.

### Relasi yang Sudah Ada

Model Inquiry sudah terlihat memiliki relasi:

- `quotation()` latest quotation
- `quotations()` hasMany
- `customer()` belongsTo
- `followUps()` hasMany
- `communications()` hasMany
- `activities()` morphMany
- `itineraries()` belongsToMany melalui `inquiry_itinerary_references`

Ini positif dan menunjukkan sistem sudah berkembang melebihi migration awal.

## 4. Temuan Itinerary

Migration awal `create_itineraries_table` masih minimal:

```php
id
title
duration_days
description
is_active
timestamps
```

Tetapi repository juga memiliki migration tambahan seperti:

- add inquiry_id to itineraries
- add destination to itineraries
- add created_by to itineraries
- enforce itinerary status lifecycle
- allow relation itinerary to quotations
- hotel_itinerary table
- technical docs itinerary flow
- itinerary detail map architecture

Artinya itinerary sudah dikembangkan, tetapi perlu audit apakah sudah menjadi planning engine yang benar:

```text
Inquiry → Itinerary → Quotation → Booking
```

## 5. Temuan Quotation

Repository memiliki:

- `create_quotations_table`
- `create_quotation_items_table`
- `add_itinerary_id_to_quotations_table`
- `allow_multiple_quotations_per_itinerary`
- `add_contract_rate_and_markup_to_quotation_items_table`
- `add_validation_columns_to_quotations_and_items`
- `create_quotation_item_validations_table`
- `create_service_rate_histories_table`
- `QuotationValidationController`
- `validate.blade.php`
- `QUOTATION_VALIDATION_UAT_MATRIX.md`

Ini menunjukkan modul quotation validation sudah cukup serius. Fokus audit berikutnya:

- Apakah quotation accepted terkunci?
- Apakah versioning sudah konsisten?
- Apakah quotation item menyimpan snapshot rate?
- Apakah quotation sent dicegah jika validation belum valid?
- Apakah status `valid` dipakai padahal enum database belum mendukung?

## 6. Temuan Booking

Repository memiliki:

- `BookingController.php`
- `StoreBookingRequest.php`
- `UpdateBookingRequest.php`
- `create_booking_items_table`
- booking form view

Namun perlu audit lebih lanjut:

- Apakah booking hanya bisa dibuat dari accepted quotation?
- Apakah booking menyimpan quotation_id dan itinerary snapshot?
- Apakah booking bisa generate invoice?
- Apakah status operation/payment dipisah atau masih satu status besar?

## 7. Risiko Utama yang Harus Diaudit Codex

| Area | Risiko | Priority |
|---|---|---|
| Inquiry status | Migration, model, UI, command bisa tidak sinkron | High |
| Quotation status | Status valid/validated/final bisa bentrok dengan enum lama | High |
| Itinerary | Sudah banyak patch, perlu pastikan flow tetap bersih | High |
| Booking | Perlu pastikan hanya dari accepted quotation | High |
| Invoice/payment | Perlu pastikan finance tidak bercampur dengan booking status | High |
| Adjustment | Kemungkinan belum lengkap | Medium-High |
| Settlement | Kemungkinan belum ada sebagai modul final checker | Medium-High |
| Permission | Sudah ada middleware module/permission, perlu audit role coverage | Medium |
| Debug routes | Ada route debug menu, perlu pastikan tidak aktif di production | Medium |

## 8. Rekomendasi Audit Pertama untuk Codex

Codex harus menjalankan audit tanpa edit file dulu:

1. List semua migration terkait core flow.
2. List semua model dan relasinya.
3. Cari semua penggunaan `status` di controller, blade, request, command.
4. Bandingkan status di migration vs model constants vs form options.
5. Cek semua route action penting.
6. Cek apakah ada enum DB yang menyebabkan error `Data truncated` saat status baru disimpan.
7. Buat report: already implemented, partial, missing, inconsistent, next action.

## 9. Kesimpulan Audit Awal

Project VOYEX CRM sudah memiliki fondasi yang cukup jauh:

```text
Customer → Inquiry → Itinerary → Quotation → Validation → Booking
```

Tetapi perlu penyelarasan besar pada:

```text
Status lifecycle
Business rule locking
Quotation versioning
Booking conversion rule
Invoice/payment separation
Adjustment + settlement
Production safety
```

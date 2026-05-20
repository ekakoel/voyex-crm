# BLUEPRINT VOYEX CRM
## Travel Agent Management System

## 1. Tujuan Sistem

VOYEX CRM adalah sistem operasional travel agent untuk mengelola proses dari permintaan awal customer/agent sampai transaksi selesai secara operasional dan finance.

Sistem harus mampu mengelola:

- Customer / Agent
- Inquiry dari berbagai source
- Itinerary sebagai planning engine
- Quotation sebagai pricing engine
- Quotation validation untuk rate dan markup
- Booking sebagai operation engine
- Invoice sebagai billing engine
- Payment sebagai payment tracking engine
- Operation / service date
- Adjustment / amendment setelah booking
- Settlement sampai final closed

## 2. Flow Utama

```text
Request Source
→ Customer / Agent
→ Inquiry
→ Itinerary
→ Quotation
→ Quotation Validation
→ Negotiation / Revision
→ Booking
→ Invoice
→ Payment
→ Operation / Service Date
→ Adjustment
→ Settlement
→ Closed
```

## 3. Pemisahan Fungsi Modul

| Modul | Fungsi Utama | Output |
|---|---|---|
| Customer / Agent | Data pihak yang meminta layanan | Customer profile |
| Inquiry | Permintaan awal dan follow-up sales | Qualified / lost / expired inquiry |
| Itinerary | Rencana perjalanan terstruktur | Itinerary option |
| Quotation | Penawaran harga | Quotation version |
| Quotation Validation | Validasi rate, vendor, markup | Validated quotation |
| Booking | Konfirmasi layanan | Booking record |
| Invoice | Tagihan | Invoice DP/full/balance/additional |
| Payment | Pembayaran | Confirmed payment |
| Operation | Pelaksanaan service | SPK/service status |
| Adjustment | Perubahan setelah booking | Additional charge/refund/deposit |
| Settlement | Final checking | Closed booking |

## 4. Customer / Agent Management

### Data Minimum

- Name
- Type: individual, company, agent, corporate
- Company name
- Contact person
- Phone
- Email
- Address
- Country
- Source
- Status

### Rule

- Inquiry harus terhubung ke customer atau agent.
- Hindari duplikasi berdasarkan phone, email, company name.
- Customer/agent inactive tidak boleh membuat booking baru tanpa approval.

## 5. Inquiry Management

Inquiry adalah pintu awal proses sales.

### Source

- WhatsApp
- WeChat
- LINE
- Email
- Website
- Facebook
- Instagram
- Telegram
- Walk-in
- Phone
- Referral
- Other

### Data Minimum

- Inquiry number
- Customer / Agent
- Source
- Travel date
- Pax adult / child / infant
- Destination
- Package request
- Hotel requirement
- Transport requirement
- Activity request
- Budget
- Special request
- Priority
- Deadline
- Assigned reservation
- Status
- Notes

### Rule

- Inquiry tidak boleh lanjut ke quotation jika belum qualified.
- Inquiry final jika converted_to_booking, lost, cancelled, expired, atau unqualified.
- Inquiry yang sudah converted_to_booking tidak boleh diproses ulang.

## 6. Itinerary Management

Itinerary adalah planning engine, bukan sekadar teks di quotation.

### Struktur Ideal

```text
Itinerary
├── Day 1
│   ├── Item 1
│   ├── Item 2
│   └── Item 3
├── Day 2
│   ├── Item 1
│   └── Item 2
└── Day 3
    └── Item 1
```

### Item Type

- hotel
- activity
- transport
- food_beverage
- tourist_attraction
- guide
- boat
- flight
- free_time
- meeting_point
- custom

### Field Penting Item

- Day
- Start time
- End time
- Title
- Description
- Item type
- Related service/vendor
- Location
- Latitude
- Longitude
- Duration
- Quantity
- Pricing unit
- Estimated cost
- Is chargeable
- Include in quotation
- Notes

### Rule

- Satu inquiry boleh punya banyak itinerary option.
- Satu itinerary boleh generate banyak quotation version.
- Item `is_chargeable = false` tidak masuk quotation.
- Item `include_in_quotation = false` tidak masuk quotation.
- Itinerary confirmed/converted tidak boleh diedit langsung tanpa adjustment.

## 7. Quotation Management

Quotation adalah pricing engine.

### Source Quotation

- Manual dari inquiry
- Generated from itinerary
- Revision dari quotation sebelumnya

### Rule

- Quotation wajib punya version.
- Quotation accepted tidak boleh diedit langsung.
- Jika ada perubahan, buat quotation revision/version baru.
- Quotation item harus menyimpan snapshot rate, markup, subtotal.
- Quotation converted ke booking harus terkunci.

## 8. Quotation Validation

Validasi digunakan sebelum quotation dikirim.

### Item yang Wajib Divali­dasi

- Hotel jika Hotel arranged by us
- Activity
- Food & Beverage
- Transport
- Tourist Attraction
- Guide
- Boat
- Custom chargeable service

### Rule

- Quotation tidak boleh sent jika masih ada item pending validation.
- Contract rate expired harus ditandai need_update / expired_rate.
- Validasi menyimpan validated_by dan validated_at.

## 9. Booking Management

Booking dibuat dari quotation accepted.

### Rule

- Booking hanya boleh dibuat dari accepted quotation.
- Booking harus menyimpan quotation_id dan itinerary_id jika tersedia.
- Booking menjadi pusat operational data.
- Booking tidak bisa closed jika invoice belum settled.
- Booking tidak bisa closed jika adjustment masih pending.

## 10. Invoice & Payment

### Invoice Type

- DP Invoice
- Balance Invoice
- Full Payment Invoice
- Additional Charge Invoice
- Cancellation Fee Invoice
- Refund Invoice

### Payment Type

- down_payment
- balance_payment
- full_payment
- additional_payment
- refund
- deposit

### Rule

- Payment harus linked ke invoice.
- Payment harus confirmed sebelum dihitung valid.
- Overpayment bisa menjadi refund atau deposit.
- Deposit menjadi credit balance customer/agent.

## 11. Operation / Service Date

Booking masuk operation setelah confirmed dan memenuhi payment term.

### Aktivitas Operation

- Confirm vendor
- Assign driver
- Assign guide
- Prepare operational itinerary
- Generate SPK
- Share schedule
- Service started
- Service completed
- Report issue

## 12. Adjustment / Amendment

Semua perubahan setelah booking harus dicatat sebagai adjustment.

### Jenis Adjustment

- additional_service
- service_upgrade
- service_downgrade
- cancellation_fee
- refund
- discount_adjustment
- pax_change
- date_change
- vendor_change
- manual_adjustment

### Rule

- Adjustment approved dapat menghasilkan additional invoice, revised invoice, refund, atau deposit.
- Adjustment rejected tidak boleh mempengaruhi invoice/payment.
- Adjustment applied harus terkunci.

## 13. Settlement / Closing

Booking boleh closed hanya jika:

- Service completed
- Semua invoice sudah issued/paid/void sesuai kondisi
- Semua payment sudah confirmed
- Tidak ada outstanding balance
- Tidak ada adjustment pending
- Tidak ada refund pending
- Deposit/overpayment sudah dicatat
- Profit booking sudah bisa dihitung

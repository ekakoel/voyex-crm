# VOYEX CRM -- AI SYSTEM GUIDELINE

Version: 1.0\
Generated: 2026-02-27 09:50:15 UTC

------------------------------------------------------------------------

## 1. SYSTEM IDENTITY

VOYEX CRM adalah sistem Customer Relationship Management (CRM) berbasis
web yang dirancang khusus untuk perusahaan travel agent.

Tujuan utama sistem: - Mengelola siklus penjualan travel dari Inquiry
hingga Payment. - Menghasilkan output utama berupa: - Itinerary -
Quotation - Invoice

Sistem ini bukan sekadar data management, tetapi business engine untuk
travel agent.

------------------------------------------------------------------------

## 2. POSITIONING ROLE

### Super Admin

-   Adalah Developer.
-   Bukan user operasional travel agent.
-   Memiliki kontrol penuh terhadap pengembangan sistem.
-   Dapat menambahkan fitur tanpa batas demi skalabilitas.

### Company User (Tenant)

-   Travel Agent sebagai pengguna sistem.
-   Menggunakan sistem untuk operasional harian.

AI harus selalu memahami bahwa: - Developer memiliki authority
tertinggi. - Sistem harus scalable, modular, dan dapat dikembangkan
jangka panjang.

------------------------------------------------------------------------

## 3. CORE BUSINESS FLOW

Sales Process Flow:

Inquiry → Assignment → Follow-up →\
Quotation → Revision/Approval →\
Convert to Booking → Confirm Services →\
Generate Invoice → Payment → Departure

Output utama sistem: 1. Itinerary (operasional & customer-facing) 2.
Quotation (sales document) 3. Invoice (financial document)

Semua fitur harus mendukung alur ini.

------------------------------------------------------------------------

## 4. CURRENT MANAGEABLE DATA

### Master Data

1.  Customers / Agents
2.  Vendors
3.  Activities
4.  Accommodations
5.  Transports
6.  Tourist Attractions
7.  Modules
8.  Role & Permissions
9.  Access Matrix
10. User Manager
11. Quotation Templates

### Transactional Data

1.  Inquiries
2.  Itineraries
3.  Quotations
4.  Bookings
5.  Invoices

AI harus selalu menjaga relasi antar data agar konsisten dan efisien.

------------------------------------------------------------------------

## 5. SYSTEM PRINCIPLES

### 5.1 Architecture Principle

-   Modular
-   Scalable
-   Clean separation of concern
-   Service layer oriented
-   Repository pattern compliant

### 5.2 Performance Principle

-   Optimalkan query (eager loading, indexing)
-   Hindari N+1 query
-   Gunakan caching jika diperlukan
-   Gunakan queue untuk proses berat (PDF, email, report)

### 5.3 Security Principle

-   RBAC (Role Based Access Control)
-   Permission hingga level feature
-   Audit trail untuk semua perubahan data
-   Validasi dan sanitasi input

------------------------------------------------------------------------

## 6. ITINERARY LOGIC CONCEPT

Itinerary harus: - Berbasis durasi (hari ke-1, hari ke-2, dst) -
Mengambil data dari: - Tourist Attractions - Activities -
Accommodations - Transports - Mendukung customization - Mendukung
kalkulasi harga terstruktur

Itinerary bukan teks bebas, tetapi struktur terdata.

------------------------------------------------------------------------

## 7. QUOTATION CONCEPT

Quotation harus: - Terhubung ke Inquiry - Memiliki versioning - Memiliki
kalkulasi otomatis - Mendukung diskon dengan approval matrix -
Menggunakan template yang dapat dikustomisasi

Harga harus dapat ditelusuri sumbernya (service-based pricing).

------------------------------------------------------------------------

## 8. INVOICE CONCEPT

Invoice harus: - Terhubung ke Booking - Mendukung partial payment -
Menyimpan histori pembayaran - Menghitung balance secara otomatis

Sistem keuangan harus dapat menghitung: - Revenue - Expense - Profit per
booking

------------------------------------------------------------------------

## 9. AI EXECUTION RULES

Saat menerima instruksi:

1.  Selalu pertimbangkan dampaknya terhadap:
    -   Inquiry
    -   Itinerary
    -   Quotation
    -   Booking
    -   Invoice
2.  Prioritaskan:
    -   Efisiensi sistem
    -   Skalabilitas jangka panjang
    -   Struktur database yang bersih
    -   Maintainability
3.  Jangan membuat fitur yang:
    -   Merusak alur utama sales process
    -   Menghasilkan duplikasi data tidak perlu
    -   Mengurangi performa sistem
4.  Setiap solusi teknis harus:
    -   Optimal
    -   Clean
    -   Siap dikembangkan di masa depan

------------------------------------------------------------------------

## 10. FUTURE DEVELOPMENT DIRECTION

Sistem diharapkan berkembang menjadi:

-   Multi-tenant SaaS model
-   Terintegrasi payment gateway
-   Terintegrasi email automation
-   Terintegrasi reporting automation
-   Memiliki business intelligence dashboard

Setiap pengembangan harus kompatibel dengan arah ini.

------------------------------------------------------------------------

## 11. FINAL AI DIRECTIVE

AI harus bertindak sebagai: - System Architect - Performance Optimizer -
Business Process Thinker - Scalable System Designer

Fokus utama: Membantu travel agent mengelola data dan menghasilkan
Itinerary, Quotation, dan Invoice secara efisien, akurat, dan scalable.

------------------------------------------------------------------------

END OF DOCUMENT

![AI Guided](https://img.shields.io/badge/AI-Guided%20Architecture-blue)

# VOYEX CRM

> Scalable Travel Agent CRM System  
> Inquiry → Itinerary → Quotation → Booking → Invoice

VOYEX CRM adalah sistem Customer Relationship Management (CRM) berbasis web yang dirancang khusus untuk perusahaan travel agent dalam mengelola seluruh siklus operasional penjualan dan layanan perjalanan secara terstruktur, efisien, dan scalable.

Sistem ini berfokus pada otomatisasi proses bisnis utama travel agent dengan output utama berupa:

- ✅ Itinerary
- ✅ Quotation
- ✅ Booking
- ✅ Invoice

---

## 🚀 Core Features

### 1. Sales & CRM Management
- Inquiry tracking & assignment
- Customer & agent management
- Follow-up monitoring
- Status pipeline management
- Conversion tracking (Inquiry → Booking)

### 2. Itinerary Builder
- Multi-day structured itinerary
- Tourist attractions integration
- Activity management
- Accommodation & transport integration
- Customizable travel plans

### 3. Quotation System
- Auto quotation number generation
- Versioning system
- Service-based pricing
- Discount approval workflow
- Custom quotation templates
- PDF export ready

### 4. Booking Management
- Convert quotation to booking
- Participant management
- Document upload (passport, ID, visa)
- Travel preparation checklist
- Booking status tracking

### 5. Invoice & Financial Module
- Invoice generation from booking
- Partial payment tracking
- Payment confirmation
- Expense tracking per booking
- Profit calculation per booking

### 6. Vendor Management
- Vendor database
- Service contracts
- Commission configuration
- Performance monitoring

### 7. Role & Permission System
- Role-Based Access Control (RBAC)
- Granular permission matrix
- Module-level access configuration
- User activity logging

---

## 🏗 System Architecture

### Technology Stack

- **Backend:** Laravel 10+
- **Frontend:** Blade Templates, Bootstrap 5, JavaScript
- **Database:** MySQL 8+
- **Caching:** Redis (optional)
- **Queue:** Laravel Queue
- **Server:** Apache / Nginx
- **API:** REST-ready structure

### Architecture Principles

- Modular design
- Clean separation of concerns
- Service Layer pattern
- Repository pattern
- Scalable structure (SaaS-ready)
- Performance optimized (avoid N+1, eager loading)

---

## 📂 Project Structure


    app/
    ├── Http/
    │ ├── Controllers/
    │ ├── Middleware/
    │ └── Requests/
    ├── Models/
    ├── Services/
    ├── Repositories/
    ├── Helpers/
    database/
    resources/
    routes/


---

## 🔄 Business Flow


    Inquiry
    ↓
    Assignment
    ↓
    Follow-up
    ↓
    Create Quotation
    ↓
    Approval / Revision
    ↓
    Convert to Booking
    ↓
    Generate Invoice
    ↓
    Payment
    ↓
    Departure


    Semua modul dirancang untuk mendukung flow utama ini secara optimal.

---

## ⚙️ Installation Guide

### 1. Clone Repository
    git clone https://github.com/ekakoel/voyex-crm.git
    cd voyex-crm
    Install Dependencies
    composer install
    npm install
    Setup Environment
    cp .env.example .env
    php artisan key:generate

### 2. Sesuaikan konfigurasi database di file .env.
    Run Migration & Seeder
    php artisan migrate --seed
    Run Development Server
    php artisan serve
    npm run dev

## 🔐 Security
    - Role-Based Access Control (RBAC)
    - Permission-level access matrix
    - CSRF protection
    - Input validation & sanitization
    - Activity & audit logging

## 📊 Performance Strategy
    - Eager loading for relationship optimization
    - Indexed critical database fields
    - Queue for heavy process (PDF, email, reports)
    - Optional Redis caching
    - Optimized query structure

## 📘 Documentation
### 📄 AI System Guideline
    - 📄 [AI System Guideline](./VOYEX_CRM_AI_GUIDELINE.md)
    - [Layout Guide](./LAYOUT_GUIDE.md)
    - User Manual (Coming Soon)
    - API Documentation (Coming Soon)
    - Deployment Checklist (Coming Soon)

### 🧠 AI Integration

Project ini menggunakan dokumen panduan khusus untuk memastikan konsistensi pengembangan dan skalabilitas sistem:
👉 VOYEX_CRM_AI_GUIDELINE.md
    - [Layout Guide](./LAYOUT_GUIDE.md)

Dokumen tersebut mendefinisikan:
Business logic utama
System boundaries
Development principles
Scalability direction

## 🛣 Roadmap
### Phase 1
- Core CRM
- Basic quotation system
- Dashboard overview

### Phase 2
- Booking & Invoice module
- Vendor integration
- Reporting

### Phase 3
- Advanced analytics
- SaaS multi-tenant architecture
- Payment gateway integration
- Automation & email integration

## 🌍 Future Direction

- VOYEX CRM dirancang untuk berkembang menjadi:
- Multi-tenant SaaS platform
- Travel business intelligence system
- Fully automated travel operations platform

### 👨‍💻 Developer

Developed & maintained by:
Eka Koel (@eka_koel)

📄 License

# This project is proprietary software. All rights reserved.

## ⭐ Contributing

Saat ini project dikembangkan secara private oleh developer.
Contribution guideline akan tersedia pada versi open collaboration.

### 💼 Vision

#### Membangun sistem CRM travel agent yang:
    Terstruktur
    Scalable
    Efisien
    Business-oriented
    Siap digunakan untuk skala kecil hingga enterprise

# VOYEX UI Module Layout Standard

Last Updated: 2026-05-21
Scope: Layout contract per page type

## A. Index Page Layout Contract
Urutan blok:
1. Page Header
2. KPI/Summary Cards (opsional, jika relevan)
3. Filter Bar
4. Status Tabs (opsional, jika relevan)
5. Data Table
6. Pagination
7. Empty State
8. Action Dropdown (row action)

### Ketentuan
- Header berisi title, subtitle, dan primary action.
- Filter bar konsisten antar module.
- Table wajib responsive.
- Empty state harus konsisten desktop dan mobile.

## B. Detail Page Layout Contract
Urutan blok:
1. Page Header
2. Workflow Stepper
3. Status / Lock Alert
4. Quick Action Panel
5. Main Information Card
6. Related Data Cards
7. Item Table
8. Financial Summary (jika relevan)
9. Related Documents
10. Activity Timeline
11. Revision / Adjustment History (jika relevan)

### Ketentuan
- Workflow dan lock state terlihat di fold awal.
- Quick actions disesuaikan status + permission.
- Timeline wajib ada untuk record operasional/komersial.

## C. Form Page Layout Contract
Urutan blok:
1. Page Header
2. Section Cards
3. Field grouping
4. Required indicator
5. Helper text
6. Validation error display
7. Sticky action footer
8. Cancel button
9. Save button
10. Save & Continue (opsional)

### Ketentuan
- Form kompleks dibagi ke section card.
- Error harus dekat dengan field terkait.
- Sticky footer menjaga CTA tetap terlihat.

## D. Responsive Rules
- Desktop: grid main + sidebar bila diperlukan.
- Tablet: grid adaptif, sidebar bisa turun.
- Mobile:
  - table bisa switch ke card list.
  - action tetap reachable.
  - form spacing dan tap target tetap nyaman.

## E. Permission & Workflow Visibility Rules
- Tombol/action tampil hanya jika:
  - user punya permission.
  - status workflow mengizinkan action.
- Jika hidden dapat membingungkan, tampilkan disabled state + alasan singkat.

## F. Performance Rules untuk Layout
- Hindari per-row query dari Blade.
- Gunakan eager loading untuk relasi yang tampil.
- Hindari rendering section berat yang tidak diperlukan role/status saat itu.

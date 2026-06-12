# VOYEX UI Standardization Checklist

Last Updated: 2026-05-22
Scope: UI documentation and implementation tracking

## Global Quality Gate (Wajib di Setiap Step UI)
- [ ] Semua text user-facing memakai helper translation existing.
- [ ] Semua page title, subtitle, breadcrumb, button, action, table header, form label, placeholder, alert, modal, empty state, dan status label translated.
- [ ] Semua amount/price/rate/total/balance/paid/discount/markup/cancellation fee memakai money formatter/component existing.
- [ ] Tidak ada `number_format` manual untuk nominal uang di Blade.
- [ ] Semua elemen UI yang disentuh aman pada mode light dan dark (kontras, hover/focus, readable).
- [ ] Setiap file yang diedit sudah diaudit i18n + currency + light/dark.
- [ ] Checklist dan changelog diupdate pada step yang sama.
- [ ] Untuk index/list: filter WAJIB mengikuti baseline `Inquiries` (single card, no sidebar, visible on mobile, no nested card, desktop table + mobile cards, AJAX filter+pagination, reset secondary sejajar input).

## Reusable UI Components
- [ ] Not Started
- [ ] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [x] Done

Components created/standardized:
- [x] `resources/views/components/ui/page-header.blade.php`
- [x] `resources/views/components/ui/status-badge.blade.php`
- [x] `resources/views/components/ui/workflow-stepper.blade.php`
- [x] `resources/views/components/ui/action-panel.blade.php`
- [x] `resources/views/components/ui/empty-state.blade.php`
- [x] `resources/views/components/ui/filter-bar.blade.php`
- [x] `resources/views/components/ui/data-table.blade.php`
- [x] `resources/views/components/ui/metric-card.blade.php`
- [x] `resources/views/components/ui/info-card.blade.php`
- [x] `resources/views/components/ui/timeline.blade.php`
- [x] `resources/views/components/ui/section-card.blade.php`
- [x] `resources/views/components/ui/lock-alert.blade.php`
- [x] `resources/views/components/ui/money.blade.php`
- [x] `resources/views/components/ui/date-display.blade.php`
- [x] `resources/views/components/ui/module-tabs.blade.php`
- [x] `resources/views/components/ui/table-action-dropdown.blade.php`
- [x] Table action standard: use dropdown `...` for all index table actions (including `View/Detail`).

## Dashboard
- [ ] Not Started
- [ ] In Progress
- [ ] UI Completed
- [ ] Responsive Checked
- [ ] Reviewed
- [ ] Done

## Customers / Agents
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done
- [x] Baseline modern filter standard implemented on `customers.index`:
  - all filters merged into one card,
  - only required filters are shown per module need,
  - AJAX filtering/pagination preserved (no full-page reload),
  - minimum 3-character text filter guard enforced,
  - no nested card in filter area,
  - reset button uses secondary style and is vertically aligned with input controls.

## Inquiries
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done
- [x] Official baseline for all module index pages:
  - single compact filter card,
  - filter visible across mobile, tablet, and desktop,
  - desktop table + mobile card list,
  - AJAX filter/pagination preserved,
  - reset button secondary style with aligned height/radius.

## Itineraries
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done
- [x] Filter standardized to main baseline:
  - single compact filter card,
  - no nested card,
  - active filters limited to text, destination, duration, and per-page,
  - AJAX filter/pagination preserved,
  - reset button uses secondary style and aligned height/radius.

## Quotations
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done
- [x] Index cleanup pass: compact header/filter/table layout restored.
- [x] Filter standardized to main baseline:
  - single compact filter card,
  - no nested card and no duplicate quick filter controls,
  - active filters limited to text, status, and per-page,
  - AJAX filter/pagination preserved,
  - reset button uses secondary style with aligned height/radius.

## Quotation Validation
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done

## Bookings
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done
- [x] Index cleanup pass: status quick filters merged into filter card.

## Booking Reconciliation
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done

## Vouchers
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done

## Invoices
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done
- [x] Index cleanup pass: removed duplicate content header and merged status quick filter into filter card.

## Payments
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done
- [x] Index cleanup pass: removed duplicate content header and merged status quick filter into filter card.

## Vendors / Providers
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done
- [x] Cleanup pass: duplicate header removed, main breadcrumb header retained.
- [x] Index cleanup pass: quick status filters merged into single filter card and table readability improved.

## Activities
- [ ] Not Started
- [ ] In Progress
- [ ] UI Completed
- [ ] Responsive Checked
- [ ] Reviewed
- [ ] Done

## Food & Beverage
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done

## Tourist Attractions
- [ ] Not Started
- [ ] In Progress
- [ ] UI Completed
- [ ] Responsive Checked
- [ ] Reviewed
- [ ] Done

## Transports
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done

## Hotels / Accommodations
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done

## Island Transfers
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done

## Reports
- [ ] Not Started
- [ ] In Progress
- [ ] UI Completed
- [ ] Responsive Checked
- [ ] Reviewed
- [ ] Done

## Users / Roles / Permissions
- [ ] Not Started
- [x] In Progress
- [x] UI Completed
- [x] Responsive Checked
- [x] Reviewed
- [ ] Done

## Service Manager
- [ ] Not Started
- [ ] In Progress
- [ ] UI Completed
- [ ] Responsive Checked
- [ ] Reviewed
- [ ] Done

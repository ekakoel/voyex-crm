# UI/UX PROMPT — VOYEX CRM QUOTATION WORKFLOW EXPERIENCE

Design and implement a modern, clear, high-performance UI/UX for the VOYEX CRM quotation workflow.

The goal is to make the quotation process easy to understand for Reservation, Manager, Finance, and Operations users.

---

## MAIN DESIGN OBJECTIVE

The user must immediately understand:

```text
Where is this quotation now?
What has been completed?
What is blocking it?
Who is responsible?
What is the next action?
Is it safe to send/approve/book/invoice?
```

---

## REQUIRED QUOTATION DETAIL LAYOUT

### Top Section

Show:

```text
Quotation Number + Version
Customer/Agent
Inquiry Reference
Itinerary Reference
Current Stage
Next Action
Handled By
Validity Date
Total Amount
Risk Badge
```

Example:

```text
QTN-2026-0001 v3
Current Stage: Pending Revalidation
Next Action: Validate 2 newly added service items
Handled By: Reservation - Wayan
Risk: Validity expires in 2 days
```

---

## WORKFLOW TRACKER

Show horizontal or vertical tracker:

```text
Inquiry → Itinerary → Quotation → Validation → Sent → Approval → Booking → Invoice → Payment → Operation → Finalized → Complete
```

Color rules:

```text
gray = not started
blue = current
green = completed
yellow = needs attention
red = issue/cancelled/expired
```

Each step should show:
- label
- status
- timestamp if completed
- responsible user if available
- warning icon if attention needed

---

## STATUS BADGES

Show grouped badges:

```text
Main Status
Validation
Approval
Booking
Invoice
Payment
Operation
```

Example:

```text
Status: Sent
Validation: Valid
Approval: Waiting Customer Response
Booking: Not Started
Invoice: Not Generated
Payment: Unpaid
Operation: Not Started
```

---

## CONTEXT-AWARE ACTION BUTTONS

Do not show all buttons.

Show only actions valid for current status.

Examples:

```text
draft:
- Submit for Validation
- Edit Quotation

pending_validation:
- Open Validation Page

validated:
- Mark Ready to Send

ready_to_send:
- Send Quotation

sent:
- Mark Approved
- Request Revision
- Mark Cancelled
- Mark Lost
- Add Follow-up (hanya jika belum follow-up hari ini)

under_revision:
- Save Revision
- Submit for Revalidation

approved:
- Create Booking

booking_issue:
- Create Revision from Booking Issue

waiting_payment:
- Record Payment
- Mark Pending

in_operation:
- Add Operation Adjustment

finalized:
- Generate Final Invoice

completed:
- View Summary
```

Use primary button for the most important next action.

Daily follow-up guard:
- Jika quotation sudah di-follow-up pada hari yang sama, sembunyikan action `Add Follow-up`.
- Action `Add Follow-up` muncul lagi pada hari berikutnya.

---

## FOLLOW-UP DAILY LIMIT RULE

```text
Follow-up hanya 1 kali per hari per quotation.
Jika terlewat satu hari, status follow-up ditandai overdue (follow_up_overdue).
```

UI behavior:

```text
1. Tampilkan Add Follow-up hanya jika quotation belum di-follow-up hari ini.
2. Notifikasi follow-up due/overdue tidak tampil lagi setelah follow-up hari ini dicatat.
3. Besoknya action dan notifikasi boleh muncul lagi jika belum follow-up.
```

---

## VALIDATION PAGE UI

Create a dedicated validation page.

Header:
```text
Validate Quotation
Quotation Number
Customer/Agent
Validation Progress
Validity Date
```

Progress card:
```text
8 / 10 items valid
2 items need validation
1 item validity expires soon
```

Item table columns:

```text
Date
Service Type
Description
Vendor/Provider
Contract Rate
Markup
Selling Price
Availability
Validity Date
Status
Action
```

UX:
- Description clickable opens modal.
- Status badge per item.
- Warning if rate expired.
- Warning if no vendor selected.
- Warning if selling price is below cost.
- Inline update for contract rate/markup if allowed.
- Save item validation.
- Bulk submit only when all required item data is complete.

---

## ITEM DETAIL MODAL

Modal content:

```text
Service Name
Service Type
Vendor/Provider
Contact Person
Phone
Address
Contract Rate
Markup Type
Markup Value
Selling Price
Availability
Cancellation Policy
Payment Terms
Last Validated By
Last Validated At
Validation Notes
```

Actions:
```text
Save Validation
Mark as Valid
Mark Needs Recheck
Close
```

---

## REVISION HISTORY UI

Show quotation version list:

```text
v1 Sent
v2 Revised - customer requested additional transport
v3 Approved
```

Each version should show:
- version number
- created by
- created at
- revision reason
- status
- link to view

Do not hide old versions.

---

## TIMELINE / ACTIVITY LOG

Show important events:

```text
Inquiry created
Quotation created
Item added
Validation completed
Quotation sent
Customer requested revision
Revision created
Quotation approved
Booking created
Vendor issue found
Invoice generated
Payment received
Operation adjustment added
Final invoice completed
```

Each event:
- icon
- action
- user
- timestamp
- note/reason

---

## DASHBOARD / LIST UI

Quotation list must show:

```text
Quotation Number
Customer/Agent
Status
Validation Status
Approval Status
Booking Status
Payment Status
Validity Date
Next Action
PIC
Total Amount
Last Updated
```

Filters:

```text
My Quotations
Needs Validation
Ready to Send
Waiting Customer Response
Needs Revision
Approved
Booking Issue
Waiting Payment
Overdue
In Operation
Completed
Cancelled/Lost
```

Add quick badges:
```text
Expired
Due Soon
Needs Action
Blocked
Ready
```

---

## VISUAL STYLE

Use clean enterprise CRM style:

```text
minimal
fast
clear hierarchy
status-first design
compact but readable tables
sticky action bar
consistent badges
responsive layout
low visual noise
```

Avoid:
```text
too many colors
too many buttons
unclear icons
large empty cards
repeated status labels
logic hidden only in text
```

---

## PERFORMANCE UI RULES

```text
Do not load huge tables without pagination.
Use eager-loaded data from controller.
Do not calculate totals repeatedly in Blade.
Use partials/components for reusable UI.
Avoid heavy JS if Blade + small JS is enough.
Use modal content efficiently.
Use debounce for filters/search.
```

---

## ACCEPTANCE CRITERIA

The UI/UX is accepted when:

```text
A Reservation user can understand the quotation status in under 5 seconds.
The next action is obvious.
Invalid actions are hidden.
Validation problems are visible per item.
Revision history is clear.
Booking issue can be traced back to quotation revision.
Invoice/payment status is visible.
Operation adjustment is visible.
The page remains fast with many quotation items.
```
## Update Implementasi: Revise Quotation (Versioned Flow)

- Action **Revise Quotation** pada halaman detail quotation sekarang memulai proses melalui endpoint:
  - `POST quotations/{quotation}/start-itinerary-revision`
- Flow:
  1. Sistem membuat **itinerary revision versi baru** (tidak overwrite itinerary lama).
  2. User diarahkan ke halaman edit itinerary revision.
  3. Setelah itinerary disimpan, user diarahkan kembali ke halaman **Revise Quotation**.
  4. Service item quotation disinkronkan otomatis dari itinerary revision terbaru.
  5. Item lama yang sama tetap mempertahankan status validasi; item baru/berubah masuk revalidation.

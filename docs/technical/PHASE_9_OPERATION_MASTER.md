# Phase 9 Operation Master

Last Updated: 2026-05-20

## Scope
- Konsolidasi Phase 9 + 9B:
  - operation lifecycle stabilization,
  - dispatch and SPK stabilization.

## 1) Operation Lifecycle (9)
- Core actions:
  - `markReadyToOperate`
  - `startOperation`
  - `completeService`
  - `reportOperationIssue`
- Core routes:
  - `bookings.operation.ready/start/complete/issue`
- Core permissions:
  - `bookings.operation.view/prepare/start/complete/issue`
- Payment readiness guard before `ready_to_operate`:
  - invoice `paid/overpaid` OR at least one confirmed payment exists.

## 2) Dispatch & SPK (9B)
- Dispatch fields added to `booking_items`:
  - vendor confirmation status/time/by
  - assigned driver/guide name+phone
  - dispatch status, operation notes, issue note.
- Dispatch actions:
  - `confirmItemVendor`
  - `updateItemDispatch`
  - `markItemDispatchReady`
  - `markItemDispatchCompleted`
  - `reportItemDispatchIssue`
- SPK actions/routes:
  - `bookings.spk` (+ print view flow).
- Additional permissions:
  - `bookings.operation.dispatch`
  - `bookings.operation.vendor_confirm`
  - `bookings.operation.assign_driver`
  - `bookings.operation.assign_guide`
  - `bookings.operation.spk.view`
  - `bookings.operation.spk.print`

## 3) Activity Logs
- `operation.spk_viewed`
- `operation.item_vendor_confirmed`
- `operation.item_dispatch_updated`
- `operation.item_driver_assigned`
- `operation.item_guide_assigned`
- `operation.item_ready`
- `operation.item_completed`
- `operation.item_issue_reported`

## 4) Current Business Coverage
1. Operation progression state is explicit and guarded.
2. Dispatch assignment exists without requiring dedicated driver/guide master yet.
3. SPK available as operational execution workspace.
4. Financial mutation is not mixed into operation actions.

## 5) Remaining Limits
1. Driver/guide still text assignment (not master entities yet).
2. Vendor checklist is not yet multi-step workflow.
3. SPK sharing automation (e.g., WhatsApp) not implemented.

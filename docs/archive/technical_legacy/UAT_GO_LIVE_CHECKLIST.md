# UAT Go-Live Checklist

Date: 2026-05-21

## A. End-to-End Flow

- [ ] Create customer/agent
- [ ] Create inquiry
- [ ] Assign inquiry to reservation
- [ ] Create itinerary from inquiry
- [ ] Generate quotation from itinerary
- [ ] Validate quotation
- [ ] Accept/finalize quotation according to workflow
- [ ] Convert quotation to booking
- [ ] Verify pax + itinerary snapshot
- [ ] Generate invoice
- [ ] Issue invoice
- [ ] Record payment
- [ ] Confirm payment
- [ ] Verify invoice status transition
- [ ] Mark booking ready_to_operate
- [ ] Start operation
- [ ] Confirm vendor/service item
- [ ] Fill driver/guide assignment text
- [ ] View/print SPK
- [ ] Complete service
- [ ] Create adjustment additional_service (charge)
- [ ] Submit adjustment
- [ ] Approve adjustment
- [ ] Apply adjustment
- [ ] Verify additional invoice generated
- [ ] Record + confirm additional payment
- [ ] Review settlement
- [ ] Mark settlement settled
- [ ] Close booking
- [ ] Verify booking status = closed
- [ ] Verify activity logs on critical actions

## B. Negative Scenarios

- [ ] Without confirmed payment booking cannot ready_to_operate
- [ ] Without service_completed booking cannot close
- [ ] Outstanding invoice blocks settlement
- [ ] Pending payment blocks settlement
- [ ] Pending adjustment blocks settlement
- [ ] Applied adjustment cannot be edited
- [ ] Closed booking cannot be edited unsafely
- [ ] Void/cancelled invoice cannot receive payment
- [ ] Rejected/cancelled payment does not change invoice balance
- [ ] Non-financial adjustment does not mutate invoice/payment

## C. Role & Permission

- [ ] Reservation: inquiry/itinerary/quotation/booking operation only
- [ ] Finance: invoice/payment/settlement review
- [ ] Accountant: finance view/review as configured
- [ ] Manager: review/approval as configured
- [ ] Director: settlement close booking if permitted
- [ ] Finance has no dispatch actions (unless explicitly assigned)
- [ ] Reservation cannot confirm/reject payment (unless explicitly assigned)
- [ ] `location/resolve-google-map` requires `locations.resolve_google_map`
- [ ] Settlement close button appears only for valid role + settled state

## D. Technical Lanes

- [ ] `php artisan test` default lane
- [ ] `php artisan test --configuration=phpunit.mysql.xml.example`
- [ ] `php artisan route:list` checked for debug/custom exposure
- [ ] Deployment cache commands pass

## E. Go-Live Gate

- [ ] Backup plan ready
- [ ] Rollback steps documented
- [ ] Monitoring/log owner assigned
- [ ] Scheduler/queue runtime validated
- [ ] UAT sign-off by business owner

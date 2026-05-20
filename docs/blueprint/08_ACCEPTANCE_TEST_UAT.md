# ACCEPTANCE TEST / UAT

## UAT Scenario 1 — Simple Inquiry to Booking

1. Create customer.
2. Create inquiry from WhatsApp.
3. Assign inquiry to reservation.
4. Mark inquiry as qualified.
5. Create itinerary.
6. Generate quotation from itinerary.
7. Validate quotation.
8. Send quotation.
9. Accept quotation.
10. Convert to booking.
11. Generate DP invoice.
12. Confirm DP payment.
13. Mark booking ready_to_operate.
14. Complete service.
15. Settle booking.
16. Close booking.

Expected result:

- Inquiry status = converted_to_booking
- Quotation status = converted
- Booking status = closed
- Invoice status = paid
- Settlement status = settled

## UAT Scenario 2 — Quotation Revision

1. Create inquiry.
2. Create itinerary option A.
3. Generate quotation V1.
4. Send quotation V1.
5. Customer requests revision.
6. Create quotation V2.
7. V1 becomes superseded/revised.
8. Validate V2.
9. Send V2.
10. Accept V2.
11. Convert V2 to booking.

Expected result:

- V1 is not overwritten.
- V2 becomes accepted/converted.
- Booking uses V2.

## UAT Scenario 3 — Expired Inquiry

1. Create inquiry.
2. Set deadline.
3. No response from customer.
4. Mark inquiry expired.

Expected result:

- Inquiry cannot create new quotation unless reopened by authorized role.
- Activity log records status change.

## UAT Scenario 4 — Booking Additional Service

1. Booking is confirmed.
2. During service, customer adds extra activity.
3. Create adjustment additional_service.
4. Approve adjustment.
5. System creates additional invoice.
6. Customer pays additional invoice.
7. Settlement passes.

Expected result:

- Original quotation is not directly edited.
- Adjustment is applied.
- Additional invoice is linked to booking.

## UAT Scenario 5 — Overpayment to Deposit

1. Invoice total = 10,000,000.
2. Customer pays 11,000,000.
3. System detects overpayment 1,000,000.
4. Finance allocates as deposit.

Expected result:

- Invoice status = overpaid or paid with credit note depending design.
- Payment status = confirmed.
- Deposit balance created for customer/agent.

## UAT Scenario 6 — Permission Access

Test role access:

- Super Admin: full access.
- Administrator: system/user/module management.
- Manager: approval and monitoring.
- Reservation: inquiry/itinerary/quotation/booking.
- Finance: invoice/payment/settlement.
- Accountant: financial reports.
- Marketing: inquiry/customer follow-up.
- Editor: service catalog/content.

Expected result:

- User cannot access unauthorized route.
- Sidebar only shows allowed modules.
- Direct URL access is blocked.

## Definition of Ready for Production

Project is ready if:

- [ ] Core flow can run end-to-end without error.
- [ ] Status lifecycle is consistent.
- [ ] No SQL enum/data truncated issue for statuses.
- [ ] No accepted/converted/closed data can be edited directly.
- [ ] Invoice and payment calculations are correct.
- [ ] Adjustment and settlement prevent financial mismatch.
- [ ] Role access is safe.
- [ ] Activity log exists for important actions.
- [ ] Production config is safe.
- [ ] Backup and deployment flow are ready.

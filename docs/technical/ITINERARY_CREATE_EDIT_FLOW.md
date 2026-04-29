# Itinerary Create/Edit Flow

Last Updated: 2026-04-29 (F&B meal-availability sync)


This document explains the current end-to-end itinerary create/edit flow (runtime as of 2026-04-29) without repeating show-page map details.

Main code references:
- `resources/views/modules/itineraries/create.blade.php`
- `resources/views/modules/itineraries/edit.blade.php`
- `resources/views/modules/itineraries/_form.blade.php`
- `app/Http/Controllers/Admin/ItineraryController.php`

Document scope:
- create/edit form,
- payload normalization,
- core business rules,
- risk areas during refactor.

Out of scope:
- map architecture on the detail page (`show`) -> see `ITINERARY_DETAIL_MAP_ARCHITECTURE.md`.

## 1. Struktur Halaman

`create.blade.php` and `edit.blade.php` act as wrapper layouts.

- Left column: main itinerary form (`_form.blade.php`).
- Right column: supporting panels such as Inquiry Detail, Route Preview, and Audit Info (edit).
- Main form UX uses a 4-step wizard in `_form.blade.php`:
  - Step 1: Basic Info
  - Step 2: Day Planner
  - Step 3: Include/Exclude
  - Step 4: Review

Create vs edit differences:
- Create submit ke `itineraries.store`.
- Edit submit ke `itineraries.update` (`PUT`).
- Edit loads existing itinerary + relations for prefill.

## 2. Controller Responsibilities

### `create(Request $request)`

The controller loads all master data for the form, including:
- attractions,
- activities (+ vendor location),
- food & beverage (+ vendor location),
- hotels + rooms,
- airports,
- transport units,
- destinations,
- inquiries (+ customer/assigned/latest follow-up).

### `edit(Itinerary $itinerary)`

Before rendering edit, the controller applies guard checks:
- authorization update,
- lock for `final` itineraries,
- lock when the related quotation is already `approved`.

Then it loads existing itinerary relations:
- itinerary items,
- activity items,
- food-beverage items,
- day points,
- transport units,
- inquiry link.

### `store()` and `update()`

Common flow for both:
- validate core itinerary fields,
- validate day-level arrays and schedule items,
- normalize day points,
- normalize daily transport units,
- sync itinerary relations to related tables.

Additional important flow:
- create enforces `status = pending` (`Itinerary::STATUS_PENDING`) and sets `created_by`.
- destination can be resolved to `destination_id`.
- related inquiry can auto-transition `draft -> processed` (based on status rules).
- update calls `QuotationItinerarySyncService` to sync linked quotation payload after itinerary is saved.

## 3. Data Preparation in `_form.blade.php`

The form builds initial state from two sources:
1. `old(...)` when validation fails,
2. existing itinerary data on edit.

Schedule items from 4 sources are merged first into a single row state:
- `itinerary_items` (attraction),
- `itinerary_activity_items` (activity),
- `itinerary_island_transfer_items` (island transfer from the dedicated module),
- `itinerary_food_beverage_items`.

After that, rows are sorted and grouped per day (`rowsByDay`) for dynamic DOM rendering.

Day-level state is also prepared:
- start/end point + room,
- daily transport unit,
- include/exclude,
- main experience,
- hidden payload hotel stays.

## 4. Anatomy Day Section

Each day section minimally contains:
- header day,
- `Start Tour` and `End Tour` (auto-calculated),
- `Add Item` button,
- start point,
- list `schedule-row`,
- end point,
- daily transport unit,
- include/exclude,
- hidden fields for payload.

Daily point types:
- `hotel`,
- `airport`,
- `previous_day_end` (only for start point day > 1).

If type = `hotel`, room selector is enabled based on the selected hotel.

## 5. Anatomy `schedule-row`

Each schedule row represents one itinerary item for a specific day.

Core row components:
- drag handle,
- sequence badge,
- item type,
- region/city filter,
- selector item (attraction/activity/island transfer/fnb),
- start-end time (calculated result),
- main-experience toggle,
- remove action,
- hidden travel minutes + hidden day/order.

Important notes:
- a row does not always have `name` inputs from the beginning,
- final `name` attributes are rebuilt by JS (`reindex()`) before submit.

## 6. Main JavaScript Pipeline

### 6.1 Selection and row mode

- `getRowSelection(row)`: determines the active selector based on type + value.
- `toggleType(row, type)`: switches attraction/activity/transfer/fnb mode and resets related state.
- For type `attraction`, `activity`, and `fnb`, the primary selector in Day Planner uses autocomplete input.
  - Existing data is still stored as IDs through hidden selects (`item-attraction` / `item-activity`).
  - Users can quick-add new data directly from Day Planner if not available yet.
  - Required manual format:
    - Attraction: `Attraction Name, Region, Destination`
    - Activity: `Activity Name, Region, Vendor`
    - F&B: `F&B Name, Region, Vendor`
  - When quick-add succeeds, data is saved into the related master module and still logged via model hooks.
- For type `fnb`, selectable options are now constrained by meal availability:
  - meal slot is inferred from row `start_time`,
  - slot mapping:
    - `< 11:00` => `Breakfast`
    - `11:00 - 15:59` => `Lunch`
    - `>= 16:00` => `Dinner`
  - F&B suggestions and select options in Day Planner are filtered by `meal_period` compatibility.

### 6.2 Time calculation

- `recalcDay(section)`: calculates sequence, item start/end times, and daily end-time.
- `recalcAll()`: runs calculations for all days sequentially.
- New row behavior:
  - when a new schedule row is added in Day Planner, `Start Time` is auto-populated immediately from the current day timeline.
  - if the item has not been selected yet, `End Time` stays empty until duration can be resolved from selected item data.
- Connector travel-minute behavior:
  - auto-estimate from map still runs as default.
  - users can manually edit connector minutes; manual values are preserved and will not be overwritten by auto-estimate.
  - clearing manual connector value re-enables auto-estimate for that connector.
  - if timeline recalculation changes an F&B row `start_time` and shifts its meal slot (`Breakfast/Lunch/Dinner`), selected F&B is auto-reset so user must reselect based on the new slot.
  - this reset rule applies consistently across timeline change sources, including:
    - connector travel-minute edits,
    - `Day Start Time` edits,
    - and other recalculation paths that shift row start-time.

### 6.3 Submit payload building

- `reindex()` is a critical function.
- This function assigns final field names for 3 payload groups:
  - `itinerary_items[...]`,
  - `itinerary_activity_items[...]`,
  - `itinerary_island_transfer_items[...]`,
  - `itinerary_food_beverage_items[...]`.

Without correct `reindex()`, backend payload will be out of sync.

### 6.4 Day point and visibility rules

- `syncDayPointOptionRules()`: day-dependent rules (`previous_day_end`, per-day naming).
- `syncPointItemVisibility()`: filters options by type + destination + room availability.
- `syncMainExperienceSelection()`: only one main experience per day.
- Item type `transfer` cannot be set as main experience (highlight auto-disabled).
- For `transfer`, data source is not the Activities module, but the separate `Island Transfers` module.

### 6.5 Clone, sorting, and submit

- `cloneRow()` + `bindRow()`: adds a new row with complete listeners.
- `initSortable()`: drag-drop reorder row.
- Standard submit pipeline:
  1. `autoFillAllTravelMinutesFromMap()` (best effort; async)
  2. `recalcAll()`
  3. `reindex()`
  4. sync hidden payload (hotel stays)
  5. frontend validation
  6. submit form.

## 7. Payload to Backend

### Main itinerary layer
- title,
- destination/destination_id,
- inquiry_id,
- duration days/nights,
- description,
- status/is_active.

### Layer schedule item
- `itinerary_items`
- `itinerary_activity_items`
- `itinerary_island_transfer_items`
- `itinerary_food_beverage_items`

### Day-level layer
- start/end point arrays,
- start time + travel minute,
- includes/excludes,
- main experience,
- daily transport units,
- hotel stays hidden payload.

## 8. Persistence and Normalization

In the controller, payload is processed with this pattern:
1. validation,
2. `normalizeDayPoints()`,
3. `normalizeDailyTransportUnits()`,
4. sync itinerary relations.

Additional F&B guard:
- backend validates each `itinerary_food_beverage_items[*]` row against `food_beverages.meal_period` based on row `start_time` meal slot.
- strict mode: F&B with empty/unknown `meal_period` is not eligible for timed meal-slot selection and will fail validation when selected for a defined slot.

The final result is stored in separate relational structures (not a single flat table).

## 9. Critical Business Rules

- `duration_nights` cannot exceed `duration_days`.
- duration hard limit on create/edit: `duration_days` 1..7 and `duration_nights` 0..6.
- day start/end point is allowed to stay empty; validation accepts empty point type/item values.
- `final` itineraries cannot be mutated.
- itineraries with related quotation status `approved` or `final` are locked for edit/update.
- related inquiry can change from `draft` to `processed` after save.
- F&B items auto-fill `meal_type` from `start_time`:
  - `< 11:00` => `Breakfast`
  - `11:00 - 15:59` => `Lunch`
  - `>= 16:00` => `Dinner`
- create/update mutation is owner-centric through policy/controller checks (creator + permission).

## 10. Known Risks During Refactor

1. High dependency on DOM-state + `reindex()`.
2. Current validation rule for daily transport unit still uses `exists:transports,id` while payload key is `transport_unit_id` and sync uses itinerary transport-unit relation. This is a known mismatch risk that should be addressed carefully in code refactor.
3. Clone/sort day-row can trigger drift if listeners or naming are not attached correctly.

## 11. Map Integration

- Create/edit map is a preview based on form DOM state.
- For inter-island transfer segments:
  - preview prioritizes `route_geojson` from master `island_transfers`,
  - if `route_geojson` is unavailable, it falls back to land route (OSRM) per segment.
- Show-page map is a renderer based on persisted data.

Therefore, show-page map details are separated in:
- `ITINERARY_DETAIL_MAP_ARCHITECTURE.md`.

## 12. Quick QA Checklist

1. Create a new multi-day itinerary and submit.
2. Edit an existing itinerary, verify prefill, and resubmit.
3. Test item type switching (`attraction/activity/fnb`) + reordering.
4. Test cross-area scenarios and ensure itinerary flow remains consistent when transfer items are not used.
5. Test airport-hotel start/end points including room filtering.
6. Test duration day changes (clone/remove section).
7. Ensure there are no JS errors during map preview, reindex, and submit.
8. Ensure payload is saved consistently on itinerary detail after redirect.

# STEP 1A Security and Critical Risk Cleanup

Date: 2026-05-18

## What Was Risky
- Route `GET /debug-menu/{userId}` exposed sensitive internal information:
  - user identity and roles
  - filtered menu structure
  - route existence map
- `FeatureAccessController` methods were empty; if routes are added later, endpoints could be exposed with undefined behavior.
- `FeatureAccessPolicy` methods were empty; authorization behavior was ambiguous.

## What Was Changed
- Debug route protection hardened in `routes/web.php`:
  - route now registered only when:
    - `app()->environment('local')`
    - `config('app.debug') === true`
  - route now also requires:
    - authenticated user (`auth`)
    - `Super Admin` role middleware
- `FeatureAccessController` now hard-blocks all methods with `404` response (defensive safe default).
- `FeatureAccessPolicy` now explicitly denies all actions (`return false`).
- `AuthServiceProvider` now maps:
  - `FeatureAccess::class => FeatureAccessPolicy::class`
  to ensure explicit deny policy is enforced if authorization is checked.

## Why It Was Changed
- Reduce information disclosure risk in non-local environments.
- Prevent accidental public exposure of unfinished FeatureAccess CRUD endpoints.
- Ensure deterministic authorization behavior for inactive/pending module components.

## How To Test
1. Debug route safety:
   - In non-local or when `APP_DEBUG=false`, verify `/debug-menu/{id}` is not registered.
   - In local + debug mode, verify route exists but requires login and Super Admin role.
2. FeatureAccess hard block:
   - If any route to `FeatureAccessController` is introduced, verify all actions return `404`.
3. Policy safety:
   - Verify `Gate::forUser($user)->allows('viewAny', FeatureAccess::class)` returns `false`.

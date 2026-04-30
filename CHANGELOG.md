# Changelog

All notable changes to Discountify are documented here.

## [2.0.0] — 2025-xx-xx

### Breaking changes

- **Dropped Laravel 10 support.** Minimum requirement is Laravel 11.
- **Dropped PHP 8.1 support.** Minimum requirement is PHP 8.2.
- Removed `state_file_path` config key — coupon state is now stored entirely in the database.
- Removed the JSON-file coupon driver from v1.

### Added

- Full **condition engine** with two sources:
  - Code-defined classes in `app/Conditions/` (auto-discovered, unchanged from v1)
  - **Database-driven conditions** in `discountify_conditions` — manageable via any admin UI
- **Coupon engine** backed by `discountify_coupons`:
  - Percentage and fixed discount types
  - Usage limits (global and per-user)
  - Minimum order value and maximum discount cap
  - Validity windows (`starts_at` / `expires_at`)
  - User-restricted coupons
  - Usage tracking in `discountify_coupon_usages`
- **Promo engine** backed by `discountify_promos`:
  - Auto-applied (no code required)
  - Priority ordering
  - Stackable / non-stackable flag
  - JSON condition rules (same field/operator/value syntax as DB conditions)
  - Usage tracking in `discountify_promo_usages`
- `checkout()` method — applies all discounts, records usages, fires events in one call
- Three typed events: `DiscountAppliedEvent`, `CouponAppliedEvent`, `PromoAppliedEvent`
- `CouponException` with named constructors for each failure mode
- `php artisan discountify:install` command
- GitHub Actions CI matrix: PHP 8.2/8.3/8.4 × Laravel 11/12/13
- `--type` flag on `php artisan discountify:condition`

### Changed

- `ConditionInterface` simplified — implementations only need `__invoke(array $items): bool`
- `DiscountifyServiceProvider` uses `callAfterResolving` (Laravel 11+ pattern)
- Events are now `final` classes with `readonly` constructor properties
- `CouponException` is now `final`

## [1.5.1] — 2024-10-25

Last release with Laravel 10 and PHP 8.1 support.

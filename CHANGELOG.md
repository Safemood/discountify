# Changelog

All notable changes to `Discountify` will be documented in this file.

## 1.5.0 - 2024-04-07

## What's Changed

* **Logic and Calculation Bug Fixes:** All logic and calculation bugs have been addressed and resolved to enhance the reliability and accuracy of the package.
* `totalDetailed()`: This method calculates the total with a detailed breakdown.
* `calculateTaxAmount()`: This method now calculates the tax amount, with an optional parameter to toggle between before and after discount (default is before discount).
* `savings()`: This method calculates the amount saved.
* **CouponAppliedEvent:** Added an event for when a coupon is applied.
* **DuplicateCouponException:** Implemented an exception for handling duplicate coupons.
* **Test Coverage:** Increased test coverage (99.6%) provides strong validation for the functionality of the introduced methods.
* **Type Coverage:** Achieving a robust type coverage of (98.8%) ensures the solidity and accuracy of the codebase, reinforcing its reliability and correctness.
* **Spread Cheat:** I used a spread cheat (yes excel 😅 ) to validate all results [link](https://docs.google.com/spreadsheets/d/1ki9xv1ivADVrvEEVj4L7C20mFNuN9l_S/edit#gid=1398535476).
* **Manual Validation:** Manual calculations were performed on a variety of test data scenarios to ensure the accuracy of the package results.
* Cross-checked the results with several websites to ensure accuracy:

1. [Shopify Discount Calculator](https://www.shopify.com/tools/discount-calculator)
2. [Financial Calculator](https://www.fncalculator.com/financialcalculator?type=discountCalculator)
3. [Discount Calculator](https://www.calculator.net/discount-calculator.html)


**Full Changelog**: https://github.com/Safemood/discountify/compare/1.4.3...1.5.0

## 1.4.3 - 2024-03-31

## What's Changed
* Bug Fix: Ensure Limited Usage Coupon is Applied Only Once by @Safemood in https://github.com/Safemood/discountify/pull/22


**Full Changelog**: https://github.com/Safemood/discountify/compare/1.4.2...1.4.3

## 1.4.2 - 2024-03-31

## What's Changed
* fixed bug reduce the number and not percent by @Safemood in https://github.com/Safemood/discountify/pull/20

**Full Changelog**: https://github.com/Safemood/discountify/compare/1.4.1...1.4.2

## 1.4.1 - 2024-03-27

## What's Changed
* Bump ramsey/composer-install from 2 to 3 by @dependabot in https://github.com/Safemood/discountify/pull/13
* Update composer.json for Laravel 11 support by @imabulhasan99 in https://github.com/Safemood/discountify/pull/11
* Update run-tests.yml by @imabulhasan99 in https://github.com/Safemood/discountify/pull/12
* fix tests by @Safemood in https://github.com/Safemood/discountify/pull/14
* Bump dependabot/fetch-metadata from 1.6.0 to 2.0.0 by @dependabot in https://github.com/Safemood/discountify/pull/15
* Update version constraint for illuminate/contracts by @Safemood in https://github.com/Safemood/discountify/pull/17


**Full Changelog**: https://github.com/Safemood/discountify/compare/1.4.0...1.4.1

## 1.4.0 - 2024-02-11

## What's Changed
* Added Coupon Based Discounts  by @Safemood in https://github.com/Safemood/discountify/pull/10


**Full Changelog**: https://github.com/Safemood/discountify/compare/1.3.0...1.4.0

## 1.3.0 - 2024-01-26

## What's Changed
* Fix duplicates conditions by @Safemood in https://github.com/Safemood/discountify/pull/5
* Generate conditions classes command by @Safemood in https://github.com/Safemood/discountify/pull/6
* Fix class condition not found by @Safemood in https://github.com/Safemood/discountify/pull/7
* Events tracking by @Safemood in https://github.com/Safemood/discountify/pull/8


**Full Changelog**: https://github.com/Safemood/discountify/compare/1.2.0...1.3.0

## 1.2.0 - 2024-01-21

## What's Changed
* Added the ability to skip conditions by @Safemood in https://github.com/Safemood/discountify/pull/4


**Full Changelog**: https://github.com/Safemood/discountify/compare/1.1.0...1.2.0

## 1.1.0 - 2024-01-21

## What's Changed
* added  Class-Based Conditions by @Safemood in https://github.com/Safemood/discountify/pull/3

**Full Changelog**: https://github.com/Safemood/discountify/compare/1.0.0...1.1.0

## 1.0.0 - 2024-01-17

## What's Changed
* Added dynamic fields by @Safemood in https://github.com/Safemood/discountify/pull/1
* Added slug parameter to define and defineIf methods by @Safemood in https://github.com/Safemood/discountify/pull/2

## Breaking Changes
- The `define` and `defineIf` methods now require a `slug` parameter.

**Full Changelog**: https://github.com/Safemood/discountify/compare/0.4.0...1.0.0

## 0.4.0 - 2024-01-15

**Full Changelog**: https://github.com/Safemood/discountify/compare/0.1.0...0.4.0

## 0.3.1 - 2024-01-15

**Full Changelog**: https://github.com/Safemood/discountify/compare/0.1.0...0.3.1

## 0.1.0 - 2024-01-15

**Full Changelog**: https://github.com/Safemood/discountify/commits/0.1.0

<?php

declare(strict_types=1);

namespace Safemood\Discountify\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Safemood\Discountify\Discountify;
use Safemood\Discountify\Exceptions\CouponException;
use Safemood\Discountify\Facades\Condition;
use Safemood\Discountify\Models\Condition as ConditionModel;

/**
 * API Controller for Discountify operations
 */
class DiscountifyController extends Controller
{
    public function __construct(
        protected Discountify $discountify,
    ) {}

    /**
     * Calculate discount for given items
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'global_discount' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $items = $request->input('items', []);
        $globalDiscount = $request->input('global_discount', 0);
        $taxRate = $request->input('tax_rate', 0);

        $result = $this->discountify
            ->setItems($items)
            ->setGlobalDiscount($globalDiscount)
            ->setTaxRate($taxRate)
            ->checkout();

        return response()->json([
            'success' => true,
            'data' => [
                'subtotal' => $result['subtotal'],
                'global_discount' => $result['global_discount'],
                'condition_discount' => $result['condition_discount'],
                'promo_discount' => $result['promo_discount'],
                'coupon_discount' => $result['coupon_discount'],
                'total_discount' => $result['total_discount'],
                'total' => $result['total'],
                'tax' => $result['tax'],
                'total_with_tax' => $result['total_with_tax'],
                'coupon' => $result['coupon'],
                'promos' => $result['promos'],
            ],
        ]);
    }

    /**
     * Apply a coupon code
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'items' => 'required|array',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->discountify
                ->setItems($request->input('items'))
                ->applyCoupon($request->input('code'))
                ->checkout();

            return response()->json([
                'success' => true,
                'data' => [
                    'subtotal' => $result['subtotal'],
                    'global_discount' => $result['global_discount'],
                    'condition_discount' => $result['condition_discount'],
                    'promo_discount' => $result['promo_discount'],
                    'coupon_discount' => $result['coupon_discount'],
                    'total_discount' => $result['total_discount'],
                    'total' => $result['total'],
                    'tax' => $result['tax'],
                    'total_with_tax' => $result['total_with_tax'],
                    'coupon' => $result['coupon'],
                ],
            ]);
        } catch (CouponException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get available conditions
     */
    public function conditions(): JsonResponse
    {
        $conditions = ConditionModel::orderBy('priority', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $conditions,
        ]);
    }

    /**
     * Add a condition
     */
    public function addCondition(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'required|string|max:255',
            'field' => 'required|string|max:255',
            'operator' => 'required|string|in:gt,gte,lt,lte,eq,neq,in,nin',
            'value' => 'required',
            'discount' => 'required|numeric|min:0',
            'discount_type' => 'required|string|in:percentage,fixed',
            'priority' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        ConditionModel::create([
            'name' => $request->input('name', ucfirst(str_replace('_', ' ', $request->input('slug')))),
            'slug' => $request->input('slug'),
            'field' => $request->input('field'),
            'operator' => $request->input('operator'),
            'value' => $request->input('value'),
            'discount' => $request->input('discount'),
            'discount_type' => $request->input('discount_type'),
            'priority' => $request->input('priority', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Condition added successfully',
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Branch;

/**
 * @req FSD-06, FR-POS-05, FR-POS-05a, FR-POS-06, FR-POS-07, RPT-01, RPT-08
 * Pure calculation service with 100% parity to Dart mobile implementation.
 */
final class OrderCalculator
{
    /**
     * Calculate order totals according to FSD-06 sequence.
     *
     * @param int $subtotal Sum of item prices (including modifiers) in IDR
     * @param int $promoDiscount System promo discount
     * @param int $voucherDiscount Voucher discount
     * @param int $loyaltyDiscount Redeemed points discount (1 pt = Rp 100)
     * @param int $manualDiscount Cashier manual discount
     * @param Branch $branch Branch financial configuration
     * @return array Breakdown containing all monetary fields as integers
     */
    public static function calculate(
        int $subtotal,
        int $promoDiscount,
        int $voucherDiscount,
        int $loyaltyDiscount,
        int $manualDiscount,
        Branch $branch
    ): array {
        // 1. Total discounts cannot exceed subtotal
        $totalDiscount = min($subtotal, $promoDiscount + $voucherDiscount + $loyaltyDiscount + $manualDiscount);
        $taxableBase = max(0, $subtotal - $totalDiscount);

        $serviceChargeRate = (float) $branch->service_charge_percent / 100.0;
        $taxRate = (float) $branch->tax_percent / 100.0;

        $serviceChargeAmount = 0;
        $taxAmount = 0;

        if ($branch->tax_included) {
            // Extracted tax and service charge
            // Total base = Net + Net * service + Net * tax = Net * (1 + service + tax)
            $combinedRate = 1.0 + $serviceChargeRate + $taxRate;
            $netAmount = (int) round($taxableBase / $combinedRate);
            $serviceChargeAmount = (int) round($netAmount * $serviceChargeRate);
            $taxAmount = $taxableBase - $netAmount - $serviceChargeAmount;
            $unroundedTotal = $taxableBase;
        } else {
            // Added tax and service charge
            $serviceChargeAmount = (int) round($taxableBase * $serviceChargeRate);
            // Tax base is taxableBase + serviceCharge
            $taxAmount = (int) round(($taxableBase + $serviceChargeAmount) * $taxRate);
            $unroundedTotal = $taxableBase + $serviceChargeAmount + $taxAmount;
        }

        // Rounding logic (default: round_nearest_100)
        $roundingAmount = 0;
        $finalTotal = $unroundedTotal;

        if ($branch->rounding_mode === 'round_nearest_100') {
            $remainder = $unroundedTotal % 100;
            if ($remainder !== 0) {
                if ($remainder < 50) {
                    $roundingAmount = -$remainder; // Round down
                } else {
                    $roundingAmount = 100 - $remainder; // Round up
                }
            }
            $finalTotal = $unroundedTotal + $roundingAmount;
        } elseif ($branch->rounding_mode === 'round_down_100') {
            $remainder = $unroundedTotal % 100;
            if ($remainder !== 0) {
                $roundingAmount = -$remainder;
            }
            $finalTotal = $unroundedTotal + $roundingAmount;
        } elseif ($branch->rounding_mode === 'round_up_100') {
            $remainder = $unroundedTotal % 100;
            if ($remainder !== 0) {
                $roundingAmount = 100 - $remainder;
            }
            $finalTotal = $unroundedTotal + $roundingAmount;
        }

        return [
            'subtotal' => $subtotal,
            'promo_discount' => $promoDiscount,
            'voucher_discount' => $voucherDiscount,
            'loyalty_discount' => $loyaltyDiscount,
            'manual_discount' => $manualDiscount,
            'total_discount' => $totalDiscount,
            'taxable_base' => $taxableBase,
            'service_charge_amount' => $serviceChargeAmount,
            'tax_amount' => $taxAmount,
            'unrounded_total' => $unroundedTotal,
            'rounding_amount' => $roundingAmount,
            'total_amount' => max(0, $finalTotal),
        ];
    }
}

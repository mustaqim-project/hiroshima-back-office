<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\OrderType;
use App\Models\ProductPrice;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * @req FR-PRICE-01, FR-PRICE-02, FR-PRICE-03, FR-PRICE-04, FR-PRICE-05, FR-PRICE-06, FR-PRICE-07, FSD-03
 */
class PriceResolver
{
    /**
     * Resolve unit price for any sellable item (Product, ProductVariant, Bundle, Modifier)
     * at given OrderType and Branch at a specific time WIB.
     */
    public function resolve(Model $sellable, OrderType $orderType, Branch $branch, ?CarbonInterface $at = null): int
    {
        $at = $at ?? now('Asia/Jakarta');
        $sellableType = class_basename($sellable);
        $sellableId = $sellable->getKey();

        // 1. Check for scheduled price override at branch level
        $scheduledBranchPrice = \App\Models\PriceSchedule::query()
            ->where('sellable_type', $sellableType)
            ->where('sellable_id', $sellableId)
            ->where('order_type_id', $orderType->id)
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->where('starts_at', '<=', $at)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $at))
            ->orderBy('id', 'desc')
            ->value('scheduled_price');

        if ($scheduledBranchPrice !== null) {
            return (int) $scheduledBranchPrice;
        }

        // 2. Check override cabang C untuk jenis order J
        $branchOverride = ProductPrice::query()
            ->where('sellable_type', $sellableType)
            ->where('sellable_id', $sellableId)
            ->where('order_type_id', $orderType->id)
            ->where('branch_id', $branch->id)
            ->value('price');

        if ($branchOverride !== null) {
            return (int) $branchOverride;
        }

        // 3. Check for scheduled price override at central level
        $scheduledCentralPrice = \App\Models\PriceSchedule::query()
            ->where('sellable_type', $sellableType)
            ->where('sellable_id', $sellableId)
            ->where('order_type_id', $orderType->id)
            ->whereNull('branch_id')
            ->where('is_active', true)
            ->where('starts_at', '<=', $at)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $at))
            ->orderBy('id', 'desc')
            ->value('scheduled_price');

        if ($scheduledCentralPrice !== null) {
            return (int) $scheduledCentralPrice;
        }

        // 4. Harga pusat untuk jenis order J
        $centralPrice = ProductPrice::query()
            ->where('sellable_type', $sellableType)
            ->where('sellable_id', $sellableId)
            ->where('order_type_id', $orderType->id)
            ->whereNull('branch_id')
            ->value('price');

        if ($centralPrice !== null) {
            return (int) $centralPrice;
        }

        // 5. Harga pada jenis order default (pusat atau cabang)
        $defaultOrderType = OrderType::where('is_default', true)->first();
        if ($defaultOrderType && $defaultOrderType->id !== $orderType->id) {
            $defaultPrice = ProductPrice::query()
                ->where('sellable_type', $sellableType)
                ->where('sellable_id', $sellableId)
                ->where('order_type_id', $defaultOrderType->id)
                ->where(function ($q) use ($branch) {
                    $q->where('branch_id', $branch->id)->orWhereNull('branch_id');
                })
                ->orderByRaw('branch_id IS NULL ASC') // prioritize branch override if available
                ->value('price');

            if ($defaultPrice !== null) {
                return (int) $defaultPrice;
            }
        }

        return 0;
    }
}

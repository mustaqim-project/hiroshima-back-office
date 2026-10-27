<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchInventoryPeriod;
use Carbon\CarbonInterface;

/**
 * @req FR-INV-00, FR-INV-12, FR-INV-13, FSD-05
 * Single gatekeeper for checking whether inventory tracking is active for a branch at given time.
 */
final class InventoryGate
{
    /**
     * Determine if inventory tracking is active for the given branch at time $at (WIB).
     */
    public static function isActive(int $branchId, ?CarbonInterface $at = null): bool
    {
        $at = $at ?? now('Asia/Jakarta');

        return BranchInventoryPeriod::query()
            ->where('branch_id', $branchId)
            ->where('started_at', '<=', $at)
            ->where(function ($query) use ($at) {
                $query->whereNull('ended_at')->orWhere('ended_at', '>', $at);
            })
            ->whereNotNull('activated_at') // Requires approved initial stock opname
            ->exists();
    }

    /**
     * Check if a branch is currently pending initial stock opname approval.
     */
    public static function isPendingActivation(int $branchId): bool
    {
        return BranchInventoryPeriod::query()
            ->where('branch_id', $branchId)
            ->whereNull('ended_at')
            ->whereNull('activated_at')
            ->exists();
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Device;
use App\Services\Sync\SyncEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @req FR-OFF-01, FR-OFF-02, FR-OFF-03, FR-OFF-04, FR-OFF-05, FSD-10
 */
class SyncController extends Controller
{
    public function __construct(
        protected SyncEngine $syncEngine
    ) {}

    public function pull(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = $user->branch_id ?? 1;
        $branch = Branch::findOrFail($branchId);

        $since = $request->query('since');
        $data = $this->syncEngine->pull($branch, $since);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.uuid' => 'required|uuid',
            'items.*.order_no' => 'required|string',
            'items.*.client_created_at' => 'required|date',
            'items.*.subtotal' => 'required|integer',
            'items.*.total_amount' => 'required|integer',
            'items.*.items' => 'required|array|min:1',
        ]);

        $user = $request->user();
        $branch = Branch::findOrFail($user->branch_id ?? 1);

        $deviceToken = $request->header('X-Device-Token');
        $device = $deviceToken ? Device::where('device_token_hash', hash('sha256', $deviceToken))->first() : null;

        $response = $this->syncEngine->push($branch, $device, $request->input('items'));

        return response()->json($response);
    }
}

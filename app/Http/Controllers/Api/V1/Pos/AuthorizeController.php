<?php

namespace App\Http\Controllers\Api\V1\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\PinAuthorizationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @req FSD-07, FSD-08, FSD-12
 */
class AuthorizeController extends Controller
{
    public function __construct(
        protected PinAuthorizationService $pinService
    ) {}

    public function verifyPin(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:void,discount',
            'pin' => 'required|string|size:6',
            'reason' => 'nullable|string',
        ]);

        $user = $request->user();
        $branchId = $user->branch_id ?? 1;
        $branch = Branch::findOrFail($branchId);

        try {
            $this->pinService->verify(
                $branch,
                $request->type,
                $request->pin,
                null,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Otorisasi PIN berhasil disetujui.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}

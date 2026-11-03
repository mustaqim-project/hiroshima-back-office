<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppUpdateController extends Controller
{
    public function checkUpdate(Request $request): JsonResponse
    {
        $currentBuild = (int) $request->query('build_number', 0);
        $currentVersion = $request->query('version_name', '1.0.0');

        $latestVersion = '1.0.2';
        $latestBuild = 3;
        $downloadUrl = url('/downloads/pos-release.apk');
        $changelog = "- In-App OTA Update tanpa install ulang\n- Isolasi ketat data transaksi, shift, dan hold bills antar-cabang\n- Pencegahan kebocoran data di mode offline & online\n- Peningkatan stabilitas dan kompatibilitas tema iOS Apple HIG";
        $isMandatory = false;
        $releaseDate = '2026-11-03';

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('app_settings')) {
                $latestVersion = Setting::get('pos_latest_version', $latestVersion);
                $latestBuild = (int) Setting::get('pos_latest_build', $latestBuild);
                $downloadUrl = Setting::get('pos_apk_download_url', $downloadUrl);
                $changelog = Setting::get('pos_update_changelog', $changelog);
                $isMandatory = (bool) Setting::get('pos_update_mandatory', $isMandatory);
                $releaseDate = Setting::get('pos_release_date', $releaseDate);
            }
        } catch (\Throwable $e) {
            // Graceful fallback to default values if settings table is pending or inaccessible
        }

        $hasUpdate = $currentBuild > 0 
            ? ($latestBuild > $currentBuild)
            : (version_compare($latestVersion, $currentVersion, '>'));

        return response()->json([
            'success' => true,
            'has_update' => $hasUpdate,
            'latest_version' => $latestVersion,
            'latest_build_number' => $latestBuild,
            'download_url' => $downloadUrl,
            'changelog' => $changelog,
            'is_mandatory' => $isMandatory,
            'release_date' => $releaseDate,
        ]);
    }
}

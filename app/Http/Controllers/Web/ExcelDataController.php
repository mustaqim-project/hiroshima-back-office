<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ExcelDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelDataController extends Controller
{
    public function __construct(
        protected ExcelDataService $excelService
    ) {}

    public function export(string $module): StreamedResponse
    {
        return $this->excelService->export($module, false);
    }

    public function template(string $module): StreamedResponse
    {
        return $this->excelService->export($module, true);
    }

    public function import(Request $request, string $module): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'file.required' => 'Silakan pilih file spreadsheet yang ingin diimpor.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal adalah 10 MB.',
        ]);

        $result = $this->excelService->import($module, $request->file('file'));

        if ($result['success']) {
            $response = redirect()->back()->with('success', $result['message']);
            if (!empty($result['errors'])) {
                $response->with('import_warnings', $result['errors']);
            }
            return $response;
        }

        return redirect()->back()
            ->with('error', $result['message'])
            ->with('import_errors', $result['errors'] ?? []);
    }
}

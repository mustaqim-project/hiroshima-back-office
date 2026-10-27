<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * @req FSD-18, RPT-23
 */
class AuditController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        $logs = AuditLog::with(['user', 'branch'])
            ->latest('id')
            ->paginate(25);

        $integrityStatus = $this->auditLogger->verifyIntegrity();

        return view('backoffice.audit.index', compact('logs', 'integrityStatus'));
    }
}

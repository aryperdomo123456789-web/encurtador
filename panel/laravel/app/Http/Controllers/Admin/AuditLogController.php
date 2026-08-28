<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuditLogController extends Controller
{
    private function requireOwner(Request $request): void
    {
        abort_unless((bool) optional($request->user())->isOwner(), 403);
    }

    public function index(Request $request): View
    {
        $this->requireOwner($request);

        $query = AuditLog::query()->with('actor');

        $action = trim((string) $request->string('action'));
        $subject = trim((string) $request->string('subject'));

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($subject !== '') {
            $query->where('subject_type', 'like', '%'.$subject.'%');
        }

        $logs = $query->latest('id')->paginate(25)->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'action' => $action,
            'subject' => $subject,
        ]);
    }
}

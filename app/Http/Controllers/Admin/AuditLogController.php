<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = (string) $request->input('action', '');
        $query = AuditLog::query()->with('user')->orderByDesc('id');

        if ($action !== '') {
            $query->where('action', 'like', '%'.$action.'%');
        }

        $rows = $query->limit(300)->get();
        return view('admin.audit.index', compact('rows', 'action'));
    }
}

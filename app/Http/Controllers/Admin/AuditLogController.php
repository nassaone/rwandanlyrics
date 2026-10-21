<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->atLeast(Role::Admin), 403);

        return view('admin.audit.index', ['logs' => AuditLog::with('actor')->latest('id')->paginate(50)]);
    }
}

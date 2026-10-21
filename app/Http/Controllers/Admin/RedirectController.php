<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RedirectRequest;
use App\Models\Redirect;
use App\Support\AuditLogger;
use App\Support\Redirects;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RedirectController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Redirect::class);

        $q = trim((string) $request->query('q'));

        $redirects = Redirect::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('from_path', 'like', '%'.$q.'%')->orWhere('to_path', 'like', '%'.$q.'%')))
            ->latest('id')->paginate(50)->withQueryString();

        return view('admin.redirects.index', compact('redirects'));
    }

    public function store(RedirectRequest $request): RedirectResponse
    {
        Gate::authorize('create', Redirect::class);

        Redirects::record($request->validated('from_path'), $request->validated('to_path'), $request->user());

        return redirect()->route('admin.redirects.index')->with('status', 'Redirect added.');
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        Gate::authorize('delete', $redirect);

        AuditLogger::record('redirect.deleted', $redirect, ['from' => $redirect->from_path, 'to' => $redirect->to_path]);
        $redirect->delete();

        return redirect()->route('admin.redirects.index')->with('status', 'Redirect removed.');
    }
}

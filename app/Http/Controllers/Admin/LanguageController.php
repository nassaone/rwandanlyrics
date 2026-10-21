<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LanguageRequest;
use App\Models\Language;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LanguageController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Language::class);

        return view('admin.languages.index', ['languages' => Language::orderBy('name')->paginate(50)]);
    }

    public function store(LanguageRequest $request): RedirectResponse
    {
        Gate::authorize('create', Language::class);

        $language = Language::create($request->validated());
        AuditLogger::record('language.created', $language);

        return redirect()->route('admin.languages.index')->with('status', 'Language created.');
    }

    public function edit(Language $language): View
    {
        Gate::authorize('update', $language);

        return view('admin.languages.edit', compact('language'));
    }

    public function update(LanguageRequest $request, Language $language): RedirectResponse
    {
        Gate::authorize('update', $language);

        $language->update($request->validated());
        AuditLogger::record('language.updated', $language);

        return redirect()->route('admin.languages.index')->with('status', 'Language saved.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        Gate::authorize('delete', $language);

        AuditLogger::record('language.deleted', $language);
        $language->delete();

        return redirect()->route('admin.languages.index')->with('status', 'Language deleted. Songs using it were kept.');
    }
}

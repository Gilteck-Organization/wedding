<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccessNameRequest;
use App\Models\AccessName;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccessNameController extends Controller
{
    public function index(): View
    {
        $accessNames = AccessName::query()->orderBy('name')->get();

        return view('admin.access-names.index', [
            'accessNames' => $accessNames,
        ]);
    }

    public function store(StoreAccessNameRequest $request): RedirectResponse
    {
        AccessName::query()->create($request->validated());

        return redirect()
            ->route('admin.access-names.index')
            ->with('success', 'Access name added. It works for every access-card QR verification.');
    }

    public function destroy(AccessName $accessName): RedirectResponse
    {
        $accessName->delete();

        return redirect()
            ->route('admin.access-names.index')
            ->with('success', 'Access name removed.');
    }
}

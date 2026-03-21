<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.edit', [
            'user' => request()->user(),
        ]);
    }

    public function update(UpdateAdminProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();

        return redirect()
            ->route('admin.profile.edit')
            ->with('success', 'Your profile has been updated.');
    }
}

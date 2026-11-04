<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', Rule::in(['fr', 'en', 'ar'])]]);
        $request->session()->put('locale', $data['locale']);
        app()->setLocale($data['locale']);
        Carbon::setLocale($data['locale']);
        if ($request->user()) {
            $request->user()->update(['locale' => $data['locale']]);
        }

        return back()->with('success', __('Langue mise à jour.'));
    }
}

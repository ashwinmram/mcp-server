<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    /**
     * Swap the application locale and redirect back.
     */
    public function swap(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, config('app.locales', ['en', 'ar']), true)) {
            return redirect()->back();
        }

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return redirect()->back();
    }
}

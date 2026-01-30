<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $locale = app()->getLocale();
        $dir = in_array($locale, config('app.rtl_locales', ['ar']), true) ? 'rtl' : 'ltr';

        $fallbackLocale = config('app.fallback_locale', 'en');
        $translations = [
            $locale => trans('messages', [], $locale),
            $fallbackLocale => trans('messages', [], $fallbackLocale),
        ];

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => $locale,
            'dir' => $dir,
            'locales' => [
                'en' => 'English',
                'zh' => '中文',
                'hi' => 'हिन्दी',
                'es' => 'Español',
                'fr' => 'Français',
                'ar' => 'العربية',
                'bn' => 'বাংলা',
                'pt' => 'Português',
                'ru' => 'Русский',
                'ja' => '日本語',
                'de' => 'Deutsch',
                'id' => 'Bahasa Indonesia',
                'pa' => 'ਪੰਜਾਬੀ',
                'vi' => 'Tiếng Việt',
                'tr' => 'Türkçe',
                'ko' => '한국어',
                'it' => 'Italiano',
                'fa' => 'فارسی',
                'ur' => 'اردو',
                'ta' => 'தமிழ்',
                'sw' => 'Kiswahili',
            ],
            'translations' => $translations,
        ];
    }
}

<?php
namespace App\Core\Providers;

use App\Core\Theme\ThemeManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * CoreServiceProvider
 * -------------------
 * Wires up the shared "Core" platform services. This provider grows as
 * Phase 3 lands — for now it only registers the Theme system.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singleton ThemeManager; bind both string key and class name
        $this->app->singleton('theme', fn() => new ThemeManager());
        $this->app->alias('theme', ThemeManager::class);
    }

    public function boot(): void
    {
        $this->registerThemeViewPaths();
        $this->registerThemeNamespaces();
    }

    /**
     * Prepend the active theme's view directory so view('foo.bar') resolves
     * to the theme's copy first, then falls back to resources/views/foo/bar.
     */
    protected function registerThemeViewPaths(): void
    {
        /** @var ThemeManager $themeManager */
        $themeManager = $this->app->make('theme');
        $activePath = $themeManager->path();

        if (is_dir($activePath)) {
            // Higher priority than the default views path
            View::getFinder()->prependLocation($activePath);
        }
    }

    /**
     * Make every theme accessible as a Blade namespace, e.g.:
     *   view('nexus-bms::layouts.app')
     *   view('nexus-scada::dashboard.index')
     */
    protected function registerThemeNamespaces(): void
    {
        $base = config('themes.views_path', resource_path('views/themes'));
        foreach ((array) config('themes.available', []) as $slug) {
            $path = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . $slug;
            if (is_dir($path)) {
                View::addNamespace($slug, $path);
            }
        }
    }
}

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

        // Backward-compat class aliases for files that moved to app/Core/.
        // Lets old fully-qualified names keep resolving until Phase 10 cleanup.
        $this->registerBackwardCompatAliases();
    }

    /**
     * Map old class names → new Core namespace.
     * Each entry: $oldFqcn => $newFqcn.
     */
    protected function registerBackwardCompatAliases(): void
    {
        $aliases = [
            // Phase 3.1 — Auth + Permissions
            \App\Core\Permissions\Models\Role::class             => 'App\\Models\\Role',
            \App\Core\Permissions\Models\Permission::class       => 'App\\Models\\Permission',
            \App\Core\Permissions\Middleware\CheckPermission::class => 'App\\Http\\Middleware\\CheckPermission',
            \App\Core\Auth\Controllers\LoginController::class    => 'App\\Http\\Controllers\\Auth\\LoginController',

            // Phase 3.2 — AuditLog + Notifications + Settings
            \App\Core\AuditLog\Models\ActivityLog::class         => 'App\\Models\\ActivityLog',
            \App\Core\AuditLog\Middleware\LogActivity::class     => 'App\\Http\\Middleware\\LogActivity',
            \App\Core\AuditLog\Controllers\LogController::class  => 'App\\Http\\Controllers\\LogController',
            \App\Core\Notifications\NotificationService::class   => 'App\\Services\\NotificationService',
            \App\Core\Settings\Models\SystemSetting::class       => 'App\\Models\\SystemSetting',
            \App\Core\Settings\Controllers\SettingController::class => 'App\\Http\\Controllers\\SettingController',
        ];

        foreach ($aliases as $real => $alias) {
            if (!class_exists($alias, false)) {
                class_alias($real, $alias);
            }
        }
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

<?php
namespace App\Core\Theme;

/**
 * ThemeManager — controls which theme's views/assets resolve first.
 *
 * Resolution order for view('foo.bar'):
 *   1. resources/views/themes/{active}/foo/bar.blade.php
 *   2. resources/views/foo/bar.blade.php          (legacy fallback)
 *
 * Themes also expose a Blade namespace, e.g. view('nexus-bms::foo.bar').
 */
class ThemeManager
{
    protected string $active;

    public function __construct(?string $initial = null)
    {
        $this->active = $initial
            ?? (function_exists('config') ? config('themes.default', 'nexus-bms') : 'nexus-bms');
    }

    /** Currently active theme slug. */
    public function current(): string
    {
        return $this->active;
    }

    /** Switch the active theme for the remainder of the request. */
    public function setActive(string $theme): void
    {
        $this->active = $theme;
    }

    /** Absolute filesystem path to a theme's view directory. */
    public function path(?string $theme = null): string
    {
        $base = config('themes.views_path', resource_path('views/themes'));
        return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . ($theme ?? $this->active);
    }

    /** Asset URL within a theme — typically pointing at public/themes/<slug>/. */
    public function asset(string $relativePath, ?string $theme = null): string
    {
        $base = config('themes.assets_path', 'themes');
        return asset(trim($base, '/') . '/' . ($theme ?? $this->active) . '/' . ltrim($relativePath, '/'));
    }

    /** Available theme slugs, as declared in config. */
    public function available(): array
    {
        return (array) config('themes.available', []);
    }

    /** Theme assigned to a module by config (`themes.module_defaults`). */
    public function forModule(string $module): string
    {
        $map = (array) config('themes.module_defaults', []);
        return $map[$module] ?? $this->active;
    }

    /** Convert a view key into the theme-namespaced version. */
    public function viewName(string $key, ?string $theme = null): string
    {
        return ($theme ?? $this->active) . '::' . $key;
    }
}

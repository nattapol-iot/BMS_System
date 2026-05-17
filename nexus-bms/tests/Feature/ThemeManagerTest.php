<?php

namespace Tests\Feature;

use App\Core\Theme\ThemeManager;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ThemeManagerTest extends TestCase
{
    public function test_theme_manager_singleton_resolves_from_container(): void
    {
        $a = app('theme');
        $b = app(ThemeManager::class);
        $this->assertSame($a, $b);
        $this->assertInstanceOf(ThemeManager::class, $a);
    }

    public function test_default_theme_is_nexus_bms(): void
    {
        $this->assertSame('nexus-bms', app('theme')->current());
    }

    public function test_active_theme_can_be_switched_at_runtime(): void
    {
        $mgr = app('theme');
        $mgr->setActive('nexus-scada');
        $this->assertSame('nexus-scada', $mgr->current());
        $mgr->setActive('nexus-bms'); // restore
    }

    public function test_theme_view_path_is_first_in_finder(): void
    {
        $paths = View::getFinder()->getPaths();
        $themePath = app('theme')->path();
        $this->assertSame(realpath($themePath), realpath($paths[0]),
            'Active theme path should be prepended to the view finder');
    }

    public function test_theme_namespace_resolves(): void
    {
        // The copied layout should be reachable via namespace as well.
        $resolved = View::getFinder()->find('nexus-bms::layouts.app');
        $this->assertStringContainsString('themes', $resolved);
        $this->assertStringContainsString('nexus-bms', $resolved);
        $this->assertStringEndsWith('app.blade.php', str_replace('\\', '/', $resolved));
    }

    public function test_legacy_view_key_still_resolves(): void
    {
        // view('layouts.app') must keep working — it should hit the theme copy first.
        $resolved = View::getFinder()->find('layouts.app');
        $this->assertStringEndsWith('app.blade.php', str_replace('\\', '/', $resolved));
    }

    public function test_for_module_returns_configured_theme(): void
    {
        $this->assertSame('nexus-scada', app('theme')->forModule('scada'));
        $this->assertSame('nexus-wms',   app('theme')->forModule('wms'));
        $this->assertSame('nexus-bms',   app('theme')->forModule('bms'));
        // Unknown module falls back to active
        $this->assertSame(app('theme')->current(), app('theme')->forModule('nonexistent'));
    }
}

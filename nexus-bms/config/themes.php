<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    | The theme that is active when no explicit override is set.
    | Override per-environment via THEME_DEFAULT in .env.
    */
    'default' => env('THEME_DEFAULT', 'nexus-bms'),

    /*
    |--------------------------------------------------------------------------
    | Available Themes
    |--------------------------------------------------------------------------
    | Themes registered as Blade view namespaces. Each entry must correspond
    | to a directory under resources/views/themes/<slug>/.
    */
    'available' => [
        'nexus-bms',
        'nexus-scada',
        'nexus-energy',
        'nexus-wms',
        'nexus-iiot',
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Paths
    |--------------------------------------------------------------------------
    */
    'views_path'  => resource_path('views/themes'),
    'assets_path' => 'themes',     // served from public/themes/<slug>/

    /*
    |--------------------------------------------------------------------------
    | Per-Module Default Theme
    |--------------------------------------------------------------------------
    | Used by ThemeManager::forModule($name) — modules can pin themselves to
    | a theme even if the platform default is something else.
    */
    'module_defaults' => [
        'bms'    => 'nexus-bms',
        'energy' => 'nexus-bms',     // Energy reuses BMS theme for now
        'scada'  => 'nexus-scada',
        'wms'    => 'nexus-wms',
        'iiot'   => 'nexus-iiot',
    ],

];

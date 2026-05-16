<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // @hasPermission('users', 'create') ... @endhasPermission
        Blade::if('hasPermission', function ($module, $action = 'view') {
            return auth()->check() && auth()->user()->hasPermission($module, $action);
        });

        // @isRole('admin') ... @endisRole
        Blade::if('isRole', function ($name) {
            return auth()->check() && auth()->user()->role?->name === $name;
        });
    }
}

<?php
namespace App\Modules\SCADA\Providers;

use App\Modules\SCADA\Http\Middleware\ResolveTenant;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * ScadaServiceProvider
 * --------------------
 * Boots the Nexus SCADA module:
 *   - merges config under `modules.scada`
 *   - loads web + api routes
 *   - exposes views as `scada::...` namespace
 *   - registers `scada.tenant` middleware alias
 *   - registers SCADA artisan commands
 */
class ScadaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'modules.scada');
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerMiddleware();
        $this->registerCommands();
    }

    protected function registerRoutes(): void
    {
        $webRoutes = base_path('routes/scada.php');
        $apiRoutes = base_path('routes/scada-api.php');

        if (file_exists($webRoutes)) {
            Route::middleware(['web'])->group($webRoutes);
        }
        if (file_exists($apiRoutes)) {
            // Mount under `web` so session cookies are read — the SCADA UI
            // AJAX polls these endpoints using its login session. External
            // bearer-token clients are layered in via auth:sanctum later.
            Route::middleware(['web'])->prefix('api')->group($apiRoutes);
        }
    }

    protected function registerViews(): void
    {
        // page-level views
        $modulesPath = resource_path('views/modules/scada');
        if (is_dir($modulesPath)) {
            View::addNamespace('scada', $modulesPath);
        }

        // theme is registered globally by CoreServiceProvider via
        // config/themes.php (`nexus-scada`). Nothing extra needed here.
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('scada.tenant', ResolveTenant::class);
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }
        $this->commands([
            \App\Modules\SCADA\Console\SimulateCommand::class,
            \App\Modules\SCADA\Console\PurgeHistoryCommand::class,
        ]);
    }
}

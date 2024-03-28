<?php

namespace Log1x\LaravelWebfonts;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Log1x\LaravelWebfonts\Console\Commands\WebfontsAddCommand;

class WebfontsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('laravel-webfonts', fn () => Webfonts::make($this->app));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(WebfontsAddCommand::class);
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-webfonts');

        Blade::directive('preloadFonts', fn () => "<?php echo app('laravel-webfonts')->preload()->build(); ?>");

        $this->app->make('laravel-webfonts')->handle();
    }
}

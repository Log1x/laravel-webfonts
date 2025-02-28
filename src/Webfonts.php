<?php

namespace Log1x\LaravelWebfonts;

use Illuminate\Support\Str;

class Webfonts
{
    /**
     * The fonts to preload.
     */
    protected array $fonts = [];

    /**
     * The asset manifest.
     */
    protected ?array $manifest = null;

    /**
     * The Preload Webfonts instance.
     */
    protected PreloadWebfonts $preload;

    /**
     * Determine if the WordPress handler has ran.
     */
    protected bool $wordpress = false;

    /**
     * The fonts to allow.
     */
    public static array $only = [];

    /**
     * The fonts to exclude.
     */
    public static array $except = [];

    /**
     * Create a new Webfonts instance.
     */
    public function __construct()
    {
        $this->fonts = $this->fonts();
        $this->preload = PreloadWebfonts::make($this);
    }

    /**
     * Make a new instance of Webfonts.
     */
    public static function make(): self
    {
        return new static;
    }

    /**
     * Run the Webfonts handlers.
     */
    public function handle(): self
    {
        $this->handleWordPress();

        return $this;
    }

    /**
     * Retrieve the Preload Webfonts instance.
     */
    public function preload(): PreloadWebfonts
    {
        return $this->preload;
    }

    /**
     * Set the fonts to exclude.
     */
    public static function except(array $except): void
    {
        static::$except = [...static::$except, ...$except];
    }

    /**
     * Set the fonts to allow.
     */
    public static function only(array $only): void
    {
        static::$only = [...static::$only, ...$only];
    }

    /**
     * Retrieve the fonts from the manifest.
     */
    public function fonts(array $except = [], array $only = []): array
    {
        if ($this->fonts) {
            return $this->fonts;
        }

        $except = [...static::$except, ...$except];
        $only = [...static::$only, ...$only];

        return collect($this->manifest())
            ->filter(fn ($value, $key) => Str::endsWith($key, '.woff2'))
            ->filter(fn ($value, $key) => blank($only) || in_array(basename($key), $only) || in_array(basename($key, '.woff2'), $only))
            ->reject(fn ($value, $key) => in_array(basename($key), $except) || in_array(basename($key, '.woff2'), $except))
            ->all();
    }

    /**
     * Handle the font preload markup for WordPress.
     */
    protected function handleWordPress(): void
    {
        if ($this->wordpress || ! $this->isWordPress() || ! $this->fonts()) {
            return;
        }

        add_filter('wp_head', function () {
            if (! $markup = $this->preload()->build()) {
                return;
            }

            echo "{$markup}\n";
        }, 5);

        $this->wordpress = true;
    }

    /**
     * Retrieve the asset manifest.
     */
    protected function manifest(): array
    {
        return $this->manifest ??= $this->viteManifest();
    }

    /**
     * Retrieve the Vite manifest.
     */
    protected function viteManifest(): array
    {
        if (! file_exists($manifest = public_path('build/manifest.json'))) {
            return [];
        }

        $manifest = json_decode(file_get_contents($manifest), true);

        return collect($manifest)
            ->map(fn ($value, $key) => $value['file'])
            ->all();
    }

    /**
     * Determine if the application is running WordPress.
     */
    protected function isWordPress(): bool
    {
        return class_exists('\WP') && function_exists('\add_filter');
    }
}

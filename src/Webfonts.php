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
    protected array $manifest = [];

    /**
     * The Preload Webfonts instance.
     */
    protected PreloadWebfonts $preload;

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
        return new static();
    }

    /**
     * Retrieve the Preload Webfonts instance.
     */
    public function preload(): PreloadWebfonts
    {
        return $this->preload;
    }

    /**
     * Retrieve the fonts from the manifest.
     */
    public function fonts(): array
    {
        if ($this->fonts) {
            return $this->fonts;
        }

        return collect($this->manifest())
            ->filter(fn ($value, $key) => Str::endsWith($key, '.woff2'))
            ->toArray();
    }

    /**
     * Retrieve the asset manifest.
     */
    protected function manifest(): array
    {
        if ($this->manifest) {
            return $this->manifest;
        }

        if ($manifest = $this->budManifest()) {
            return $this->manifest = $manifest;
        }

        return $this->manifest = $this->viteManifest();
    }

    /**
     * Retrieve the Bud manifest.
     */
    protected function budManifest(): array
    {
        return file_exists($manifest = public_path('manifest.json'))
            ? json_decode(file_get_contents($manifest), true)
            : [];
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
            ->map(fn ($value, $key) => "build/{$value['file']}")
            ->all();
    }
}

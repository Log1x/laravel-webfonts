<?php

namespace Log1x\LaravelWebfonts;

use Illuminate\Support\Str;

class PreloadWebfonts
{
    /**
     * The Webfonts instance.
     */
    protected $webfonts;

    /**
     * Create a new Preload Fonts instance.
     *
     * @return void
     */
    public function __construct(Webfonts $webfonts)
    {
        $this->webfonts = $webfonts;
    }

    /**
     * Make a new instance of Preload Fonts.
     */
    public static function make(Webfonts $webfonts): self
    {
        return new static($webfonts);
    }

    /**
     * Build the font preload markup.
     */
    public function build(): string
    {
        return collect($this->webfonts()->fonts())
            ->map(fn ($font) => $this->asset($font))
            ->map(fn ($font) => "<link rel='preload' href='{$font}' as='font' type='font/woff2' crossorigin>")
            ->implode(PHP_EOL);
    }

    /**
     * Retrieve the Webfonts instance.
     */
    protected function webfonts(): Webfonts
    {
        return $this->webfonts;
    }

    /**
     * Retrieve the public asset URL for the given file.
     */
    protected function asset(string $file): string
    {
        return ! $this->isAcorn()
            ? asset($file)
            : \Roots\asset($file);
    }

    /**
     * Determine if Acorn is installed.
     */
    protected function isAcorn(): bool
    {
        return function_exists('\Roots\asset');
    }

    /**
     * Determine if WordPress is available.
     */
    protected function isWordPress(): bool
    {
        return class_exists('\WP') && function_exists('\add_filter');
    }

    /**
     * Handle preloading on WordPress.
     */
    protected function handleWordPress(): void
    {
        if (! $this->isAcorn() || ! $this->isWordPress()) {
            return;
        }

        add_filter('wp_head', function () {
            if (! $this->fonts()) {
                return;
            }

            echo Str::finish($this->build(), PHP_EOL);
        }, 5);
    }
}

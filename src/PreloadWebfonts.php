<?php

namespace Log1x\LaravelWebfonts;

class PreloadWebfonts
{
    /**
     * The Webfonts instance.
     */
    protected Webfonts $webfonts;

    /**
     * The font preload markup.
     */
    protected ?string $markup = null;

    /**
     * Create a new Preload Fonts instance.
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
    public function build(): ?string
    {
        if ($this->markup) {
            return $this->markup;
        }

        if (! $fonts = $this->webfonts()->fonts()) {
            return null;
        }

        return $this->markup = collect($fonts)
            ->map(fn ($font) => $this->asset($font))
            ->map(fn ($font) => "<link rel='preload' href='{$font}' as='font' type='font/woff2' crossorigin>")
            ->implode("\n");
    }

    /**
     * Handle the font preload markup for WordPress.
     */
    public function handleWordPress(): void
    {
        if (! $this->isWordPress()) {
            return;
        }

        add_filter('wp_head', function () {
            if (! $markup = $this->build()) {
                return;
            }

            echo "{$markup}\n";
        }, 5);
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
     * Determine if the application is running WordPress.
     */
    protected function isWordPress(): bool
    {
        return class_exists('\WP') && function_exists('\add_filter');
    }
}

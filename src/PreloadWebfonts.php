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
     * Retrieve the asset URL.
     */
    protected function asset(string $font): string
    {
        return function_exists('\Roots\asset')
            ? \Roots\asset($font)
            : asset("build/{$font}");
    }

    /**
     * Retrieve the Webfonts instance.
     */
    protected function webfonts(): Webfonts
    {
        return $this->webfonts;
    }
}

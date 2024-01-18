<?php

namespace Log1x\LaravelWebfonts\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ZipArchive;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class WebfontsAddCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webfonts:add
                            {--path=css : The font stylesheet path}
                            {--stylesheet=fonts : The font stylesheet filename}
                            {--extension= : The font stylesheet extension}
                            {--clear-cache : Clear the font cache}
                            {--force : Force the download of the fonts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add web fonts to the project';

    /**
     * The Google webfonts.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $fonts;

    /**
     * The selected fonts.
     *
     * @var array
     */
    protected $selected = [];

    /**
     * The downloaded fonts.
     *
     * @var array
     */
    protected $downloaded = [];

    /**
     * The font faces.
     *
     * @var array
     */
    protected $faces = [];

    /**
     * The fonts stylesheet path.
     *
     * @var string
     */
    protected $stylesheet;

    /**
     * The Google webfonts API endpoint.
     *
     * @var string
     */
    protected $api = 'https://gwfh.mranftl.com/api/fonts';

    /**
     * The cache key.
     *
     * @var string
     */
    protected $cache = 'google-webfonts';

    /**
     * The cache expiry.
     *
     * @var int
     */
    protected $expiry = 86400;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('clear-cache')) {
            cache()->forget($this->cache);
        }

        $this->fonts = spin(
            fn () => $this->fonts(),
            'Fetching fonts from the <fg=blue>Google Webfonts Helper API</>...'
        );

        if ($this->fonts->isEmpty()) {
            $this->components->error('Unable to fetch fonts from the API.');

            cache()->forget($this->cache);

            return;
        }

        $this->selected = multisearch(
            label: 'Select the fonts you would like to add to your project',
            options: fn (string $value) => strlen($value) > 0 ?
                $this->fonts
                    ->filter(fn ($font) => Str::contains(Str::lower($font->family), Str::lower($value)))
                    ->mapWithKeys(fn ($font) => [$font->id => $font->family])
                    ->all()
                : [],
            scroll: 10,
            placeholder: 'Inter',
            hint: "<fg=blue>{$this->fonts->count()}</> fonts available.",
            required: 'You must select at least one font.',
        );

        foreach ($this->selected as $key => $font) {
            $font = $this->fonts->get($font);

            $variants = multiselect(
                label: "Select the variants you would like to add to {$font->family}",
                options: $font->variants,
                scroll: 10,
                required: 'You must select at least one variant.',
                default: [$font->defVariant],
            );

            $subsets = multiselect(
                label: "Select the subsets you would like to add to {$font->family}",
                options: $font->subsets,
                scroll: 10,
                required: 'You must select at least one subset.',
                default: [$font->defSubset],
            );

            $this->selected[$key] = [
                'id' => $font->id,
                'name' => $font->family,
                'variants' => $variants,
                'subsets' => $subsets,
            ];
        }

        $selected = collect($this->selected);

        $this->line("  <fg=blue>❯</> The following <fg=blue>{$selected->count()}</> fonts will be added to your project:");

        table(
            ['<fg=blue>Name</>', '<fg=blue>Variants</>', '<fg=blue>Subsets</>'],
            $selected->map(function ($font) {
                $variants = Str::limit(
                    collect($this->fonts->get($font['id'])->variants)
                        ->filter(fn ($variant, $key) => in_array($key, $font['variants']))
                        ->implode(', '),
                    25
                );

                $subsets = Str::limit(
                    collect($this->fonts->get($font['id'])->subsets)
                        ->filter(fn ($subset, $key) => in_array($key, $font['subsets']))
                        ->implode(', '),
                    25
                );

                return [
                    $font['name'],
                    $variants,
                    $subsets,
                ];
            })->all()
        );

        $count = count($this->selected);
        $confirmed = confirm("You are about to add {$count} font(s) to your project. Do you wish to continue?");

        if (! $confirmed) {
            return;
        }

        foreach ($this->selected as $font) {
            $this->line("  <fg=blue>❯</> Adding <fg=blue>{$font['name']}</> to the project...");
            $this->download($font);
        }

        foreach ($this->downloaded as $name => $fonts) {
            $this->line("  <fg=blue>❯</> Adding <fg=blue>{$name}</> to the fonts stylesheet...");
            $this->addFamily($name, $fonts);
        }

        $names = collect($this->selected)->map(fn ($font) => $this->faces || $this->downloaded ? "<fg=blue>{$font['name']}</>" : "<fg=red>{$font['name']}</>");

        $names = $names->count() > 1
            ? $names->splice(0, -1)->implode(', ').' and '.$names->last()
            : $names->first();

        if (! $this->faces && ! $this->downloaded) {
            $this->components->error("Failed to add {$names} to the project.");

            $this->output->write("\033[1A");

            return;
        }

        $names = Str::contains($names, 'and') ? "{$names} have" : "{$names} has";

        $this->components->info("🎉 {$names} been successfully added to the project.");

        $this->output->write("\033[1A");
    }

    /**
     * Add the font family to the stylesheet.
     *
     * @return void
     */
    protected function addFamily(string $name, array $fonts)
    {
        if (! File::exists($stylesheet = $this->stylesheet())) {
            File::put($stylesheet, '');
        }

        foreach ($fonts as $font) {
            $type = Str::of($font)->afterLast('-')->beforeLast('.')->__toString();
            $weight = preg_replace('/[^0-9]/', '', $type) ?: 400;
            $style = Str::after($type, $weight);

            if (! $style || $style === 'regular') {
                $style = 'normal';
            }

            $face = view('laravel-webfonts::font-face', [
                'name' => $name,
                'weight' => $weight,
                'style' => $style,
                'path' => "../fonts/{$font}",
            ])->render();

            if (Str::contains(File::get($stylesheet), $face)) {
                if ($style) {
                    $style = Str::headline($style);
                }

                $type = $weight && $style ? "{$weight} {$style}" : ($weight ?: $style);

                $this->components->warn("<fg=yellow>{$name}</> <fg=gray>({$type})</> already exists in the stylesheet.");

                continue;
            }

            $this->faces[] = $face;
        }

        if (! $this->faces) {
            return;
        }

        File::prepend($stylesheet, implode(PHP_EOL, $this->faces).PHP_EOL);
    }

    /**
     * Download the font to the project.
     *
     * @return void
     */
    protected function download(array $font)
    {
        $response = Http::withQueryParameters([
            'download' => 'zip',
            'formats' => 'woff2',
            'variants' => implode(',', $font['variants']),
            'subsets' => implode(',', $font['subsets']),
        ])->get("{$this->api}/{$font['id']}");

        if ($response->failed()) {
            $this->components->error("Failed to download <fg=red>{$font['name']}</> to the project.");

            return;
        }

        File::ensureDirectoryExists($tempPath = storage_path('app/.fonts'));

        File::put(
            $tempFile = "{$tempPath}/{$font['id']}.zip",
            $response->body()
        );

        $zip = new ZipArchive;
        $archive = $zip->open($tempFile);

        if ($archive !== true) {
            $this->components->error("Failed to unzip <fg=red>{$font['name']}</>.");

            return;
        }

        $zip->extractTo($tempPath = "{$tempPath}/{$font['id']}");
        $zip->close();

        $fileList = File::allFiles($tempPath);

        File::ensureDirectoryExists($path = resource_path('fonts'));

        $existing = collect(File::allFiles($path))
            ->map(fn ($file) => $file->getFilename())
            ->all();

        foreach ($fileList as $file) {
            if (! $this->option('force') && in_array($file->getFilename(), $existing)) {
                $confirmed = confirm("The font {$file->getFilename()} already exists. Do you wish to overwrite it?");

                if (! $confirmed) {
                    continue;
                }
            }

            $this->downloaded[$font['name']][] = $file->getFilename();

            File::move($file->getPathname(), "{$path}/{$file->getFilename()}");
        }

        File::deleteDirectory($tempPath);
    }

    /**
     * Retrieve the fonts.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function fonts()
    {
        return cache()->remember($this->cache, $this->expiry, function () {
            $fonts = Http::get($this->api);

            if ($fonts->failed()) {
                return collect();
            }

            $fonts = json_decode($fonts->body());

            return collect($fonts)->mapWithKeys(function ($font) {
                $font->variants = collect($font->variants)
                    ->flip()
                    ->forget($font->defVariant)
                    ->flip()
                    ->prepend($font->defVariant)
                    ->mapWithKeys(fn ($variant) => [$variant => preg_replace('/([0-9])([a-zA-Z])/', '$1 $2', Str::headline($variant))])
                    ->all();

                $font->subsets = collect($font->subsets)
                    ->flip()
                    ->forget($font->defSubset)
                    ->flip()
                    ->prepend($font->defSubset)
                    ->mapWithKeys(fn ($subset) => [$subset => Str::headline($subset)])
                    ->all();

                return [$font->id => $font];
            });
        });
    }

    /**
     * Retrieve the fonts stylesheet path.
     *
     * @return string
     */
    protected function stylesheet()
    {
        if ($this->stylesheet) {
            return $this->stylesheet;
        }

        $path = resource_path($this->option('path'));
        $filename = $this->option('stylesheet');

        if (! File::isDirectory($path)) {
            $path = resource_path('styles');

            if (! File::isDirectory($path)) {
                throw new Exception('Unable to locate the styles directory.');
            }
        }

        $extension = $this->option('extension') ?? collect(File::allFiles($path))
            ->map(fn ($file) => $file->getExtension())
            ->filter(fn ($extension) => in_array($extension, ['css', 'less', 'sass', 'scss', 'styl']))
            ->first();

        if (! Str::contains($filename, '.')) {
            $filename = "{$filename}.{$extension}";
        }

        return $this->stylesheet = "{$path}/{$filename}";
    }
}

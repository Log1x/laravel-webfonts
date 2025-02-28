# Laravel Webfonts

![Latest Stable Version](https://img.shields.io/packagist/v/log1x/laravel-webfonts.svg?style=flat-square)
![Total Downloads](https://img.shields.io/packagist/dt/log1x/laravel-webfonts.svg?style=flat-square)
![Build Status](https://img.shields.io/github/actions/workflow/status/log1x/laravel-webfonts/main.yml?branch=main&style=flat-square)

Laravel Webfonts allows you to easily download, install, and preload over 1500 Google fonts locally in your Laravel project.

![Demo](https://i.imgur.com/JgotyKK.gif)

## Features

- 🔍️ Search and install over 1500 Google fonts from the public [google-webfonts-helper](https://github.com/majodev/google-webfonts-helper) API.
- ⚡️ Automatically generate `@font-face` CSS `at-rules` when installing fonts using CLI.
- 🧑‍💻 Supports [Vite](https://vitejs.dev/) out of the box with zero configuration.
- ⚡️ Provides an easy-to-use `@preloadFonts` Blade directive to preload fonts found in the Vite manifest.
- 🚀 Automatically injects font preload markup into `wp_head` on WordPress sites running [Acorn](https://github.com/roots/acorn).

## Requirements

- [PHP](https://secure.php.net/manual/en/install.php) >= 8.1
- [Composer](https://getcomposer.org/download/)
- [Laravel](https://github.com/laravel/laravel) >= 10.0

## Installation

Install via Composer:

```sh
$ composer require log1x/laravel-webfonts
```

## Usage

If you already have fonts locally installed in your project, skip to [Preloading Fonts](#preloading-fonts).

### Adding Fonts

Laravel Webfonts provides a very easy way to install new webfonts to your project using command line:

```sh
artisan webfonts:add
```

By default, installing a font will trigger the following things to happen:

- Download the font archive to a temporary directory in local storage.
- Extract the font archive.
- Move downloaded fonts to `resources/fonts`.
- Clean up the temporary directory.
- Generate and prepend `@font-face` at-rules to a `fonts` stylesheet.

The fonts stylesheet will reside at the root of your stylesheet directory located in `resources/`. If the font stylesheet does not already exist, it will be created using the most common stylesheet extension (css, scss, ...) found among your styles.

By default, the `resources/css` and `resources/styles` directories are automatically scanned for existing files to find the appropriate place to write the fonts stylesheet.

The generated `@font-face` at-rules will look like this:

```css
@font-face {
  font-display: swap;
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 400;
  src: url('../fonts/roboto-v30-latin-regular.woff2') format('woff2');
}

@font-face {
  font-display: swap;
  font-family: 'Roboto';
  font-style: italic;
  font-weight: 400;
  src: url('../fonts/roboto-v30-latin-italic.woff2') format('woff2');
}
```

Adding additional fonts will cause them to be prepended to the existing `fonts` stylesheet.

### Importing Fonts

When fonts are installed for the first time, a `fonts` stylesheet is created in your project's stylesheet folder. In a vanilla Laravel project, this is typically `resources/css/fonts.css`.

You must import the generated `fonts` file into your project's primary stylesheet (e.g. `app.css`). If you're using Tailwind, it would look something like:

```css
@import 'fonts';

@tailwind base;
@tailwind components;
@tailwind utilities;
```

### Preloading Fonts

> [!NOTE]
> If you are using WordPress alongside [Acorn](https://github.com/roots/acorn), you can ignore this section as preloading is automatically handled for you inside of `wp_head` if an asset manifest containing valid fonts is detected.

Laravel Webfonts primary functionality while in production is to provide a simple way to preload your locally hosted webfonts.

This is done by reading the compiled `woff2` fonts from your Vite manifest and generating the appropriate markup for you to place inside of `<head>`.

In most cases, you can simply use the `@preloadFonts` Blade directive to handle building and echoing the font preload HTML markup.

Alternatively to the Blade directive, you can access the `PreloadFonts` class directly using the `Webfonts` Facade:

```php
use Log1x\LaravelWebfonts\Facades\Webfonts;

// Retrieve an array of compiled font paths.
$fonts = Webfonts::fonts();

// Build the font preload HTML markup.
$html = Webfonts::preload()->build();
```

Allowing/excluding certain fonts from being preloaded can be done inside `register()` of a service provider:

```php
use Log1x\LaravelWebfonts\Webfonts;

// Allow specific fonts.
Webfonts::only(['inter-v13-latin-regular']);

// Exclude specific fonts.
Webfonts::except(['inter-v13-latin-500']);
```

## Bug Reports

If you discover a bug in Laravel Webfonts, please [open an issue](https://github.com/log1x/laravel-webfonts/issues).

## Contributing

Contributing whether it be through PRs, reporting an issue, or suggesting an idea is encouraged and appreciated.

## License

Laravel Webfonts is provided under the [MIT License](LICENSE.md).

# RH SEO

A lightweight SEO plugin I use on most of my WordPress websites.

> [!CAUTION]
> This plugin is provided without public support. No issues, no discussions, no accepted PRs.
> You can browse the source code if you wish and fork it if you find something useful.

## Installation

The plugin ships as one directory regardless of how it is installed: its dependencies are committed, so the composer package and the release zip contain the same files. Sites that install via composer can therefore also update through the WP admin.

A WP admin update still leaves the site's `composer.lock` at the version composer installed. Run `composer update hirasso/rh-seo` afterwards, or the next `composer install` reinstates the older version.

### Via Composer (recommended)

```shell
composer require hirasso/rh-seo
```

#### Update via Composer

```shell
# latest version, including major version jumps
composer require hirasso/rh-seo
```

### Via WP CLI

```shell
wp plugin install https://github.com/hirasso/rh-seo/releases/latest/download/rh-seo.zip
```

#### Update via WP CLI

```shell
wp plugin update rh-seo
```

### Manually

1. Download the [bundled zip file from the latest release](https://github.com/hirasso/rh-seo/releases/latest/download/rh-seo.zip)
2. In your browser, login to your WP Admin Area and navigate to /wp-admin/plugin-install.php
3. Click "Upload Plugin" and choose `rh-seo.zip` that you just downloaded

#### Update Manually

If `DISALLOW_FILE_MODS` is not set to `true`, you can update the plugin directly from the WordPress admin plugins screen, just like any other plugin. To avoid being blocked by GitHub, you can define a `RHSEO_GITHUB_TOKEN` in your `wp-config.php` file. Learn more about [creating GitHub Tokens](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens#creating-a-fine-grained-personal-access-token)

```php
// wp-config.php:
define('RHSEO_GITHUB_TOKEN', 'qwf_sdgad142...');
```

## Development

```shell
npm ci              # also installs the pre-commit hook (rebuilds assets/)
composer install    # dev dependencies, incl. the ones copied to vendor-prefixed/
composer prefix     # regenerate vendor-prefixed/ (Strauss)
tools/verify-prefixed.sh
npm run build       # build assets/ from assets-src/
npm run generate-pot
./release.sh <patch|minor|major>
```

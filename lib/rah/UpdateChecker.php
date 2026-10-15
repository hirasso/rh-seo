<?php

namespace RAH\SEO;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
use YahnisElsts\PluginUpdateChecker\v5p7\PucFactory as VersionedPucFactory;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Check for updates using Plugin Update Checker
 * @see https://github.com/YahnisElsts/plugin-update-checker
 */
class UpdateChecker
{
    public static function init(string $entryPoint): void
    {
        self::loadLibrary();

        /** Prevent duplication of updates in rh-updater */
        add_filter('rh-updater/allow/slug=rh-seo', '__return_false');

        $checker = PucFactory::buildUpdateChecker(
            'https://github.com/hirasso/rh-seo/',
            $entryPoint,
            'rh-seo',
        );

        $checker->setBranch('main');

        if ($token = self::getGitHubToken()) {
            $checker->setAuthentication($token);
        }

        /**
         * Expect a "rh-seo.zip" attached to every release (see release.sh)
         * @var \YahnisElsts\PluginUpdateChecker\v5p7\Vcs\GitHubApi $api
         */
        $api = $checker->getVcsApi();
        $api->enableReleaseAssets('/rh-seo\.zip/i', $api::REQUIRE_RELEASE_ASSETS);

        $checker->addFilter('vcs_update_detection_strategies', [self::class, 'updateStrategies'], 999);
    }

    /**
     * Load the bundled copy of plugin-update-checker.
     * Vendored into lib/ so composer installs don't pull it into the consuming project.
     * A second copy is safe to load; skipping it just avoids a redundant autoloader.
     */
    private static function loadLibrary(): void
    {
        if (class_exists(VersionedPucFactory::class, false)) {
            return;
        }

        require_once dirname(__DIR__) . '/plugin-update-checker/plugin-update-checker.php';
    }

    /**
     * Get the RHSEO_GITHUB_TOKEN for authenticated GitHub requests
     */
    private static function getGitHubToken(): ?string
    {
        if (
            defined('RHSEO_GITHUB_TOKEN')
            && is_string(RHSEO_GITHUB_TOKEN)
            && !empty(trim(RHSEO_GITHUB_TOKEN))
        ) {
            return RHSEO_GITHUB_TOKEN;
        }
        return null;
    }

    /**
     * Only keep the "latest_release" strategy (GitHub releases are created by release.sh)
     */
    public static function updateStrategies(array $strategies): array
    {
        return ['latest_release' => $strategies['latest_release']];
    }
}

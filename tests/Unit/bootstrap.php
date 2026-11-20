<?php

/**
 * WP integration test bootstrap, runs inside the wp-env test env (`npm run test:pest`)
 */

namespace RAH\SEO\Tests\Unit;

/** Before WordPress, for the PHPUnit polyfills */
require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';

/** wp-env provides the WordPress PHPUnit test files in WP_TESTS_DIR */
$testsDir = \getenv('WP_TESTS_DIR');

require_once $testsDir . '/includes/functions.php';

/** The test install activates no plugins, so load ACF and the plugin by hand */
\tests_add_filter('muplugins_loaded', static function () {
    require_once WP_PLUGIN_DIR . '/advanced-custom-fields-pro/acf.php';
    require_once \dirname(__DIR__, 2) . '/rh-seo.php';
});

require_once $testsDir . '/includes/bootstrap.php';

/**
 * wp-phpunit's expectDeprecated() calls an API removed in PHPUnit 10+.
 * No @expectedDeprecated annotations are used here.
 */
class TestCase extends \WP_UnitTestCase
{
    public function expectDeprecated(): void {}
}

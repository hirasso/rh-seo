<?php

/**
 * Plugin Name: RH SEO
 * Version: 1.5.5
 * Author: Rasso Hilber
 * Description: Lightweight SEO optimizations for WordPress
 * Author URI: https://rassohilber.com
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 * GitHub Plugin URI: hirasso/rh-seo
 **/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(__DIR__ . '/lib/vendor/autoload.php');

define('RHSEO_PATH', plugin_dir_path(__FILE__));
define('RHSEO_BASENAME', plugin_basename(__FILE__));
define('RHSEO_UPGRADE_VERSION', '1.3.9'); // Highest version with an upgrade routine.

/**
 * Initialize the $seo instance
 *
 * @return \RAH\SEO\SEO
 */
function rhseo()
{
    static $instance;
    if (!isset($instance)) {
        $instance = new RAH\SEO\SEO();
    }
    return $instance;
}
rhseo();

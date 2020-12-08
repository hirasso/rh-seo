<?php
/**
 * Plugin Name: RH SEO
 * Version: 1.0.1
 * Author: Rasso Hilber
 * Description: Lightweight SEO optimizations for WordPress
 * Author URI: https://rassohilber.com
**/

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
* Require Modules
*/
require_once(__DIR__ . '/inc/class.singleton.php');
require_once(__DIR__ . '/inc/class.seo-meta-tags.php');

/**
 * Main Class
 */
class SEO extends Singleton {

  private $prefix = 'rhseo';

  private $deprecated_plugins = [
    'wordpress-seo/wp-seo.php'
  ];

  public function __construct() {
    
    add_action('admin_init', [$this, 'admin_init'], 11);
    add_action('admin_notices', array( $this, 'show_admin_notices'));
    
    $this->init_plugin_modules();
  }

  /**
   * Init plugin modules
   *
   * @return void
   */
  private function init_plugin_modules() {
    new SEOMetaTags($this->prefix);
  }

  /**
   * Admin init
   *
   * @return void
   */
  public function admin_init() {
    $this->delete_deprecated_plugins();
  }

  /**
   * Helper function to get versioned asset urls
   *
   * @param [type] $path
   * @return void
   */
  private function asset_uri( $path ) {
    $uri = plugins_url( $path, __FILE__ );
    $file = $this->get_plugin_path( $path );
    if( file_exists( $file ) ) {
      $version = filemtime( $file );
      $uri .= "?v=$version";
    }
    return $uri;
  }

  /**
   * Helper function to get a file path inside this plugin's folder
   *
   * @return void
   */
  function get_plugin_path( $path ) {
    $path = ltrim( $path, '/' );
    $file = plugin_dir_path( __FILE__ ) . $path;
    return $file;
  }

  /**
   * Helper function to transform an array to an object
   *
   * @param array $array
   * @return stdClass
   */
  private function to_object( $array ) {
    return json_decode(json_encode($array));
  }

  /**
   * Helper function to detect a development environment
   */
  private function is_dev() {
    return defined('WP_ENV') && WP_ENV === 'development';
  }

  /**
   * Get a template
   *
   * @param string $template_name
   * @param mixed $value
   * @return string
   */
  public function get_template($template_name, $value = null) {
    $value = $this->to_object($value);
    $path = $this->get_plugin_path("templates/$template_name.php");
    $path = apply_filters("$this->prefix/template/$template_name", $path);
    if( !file_exists($path) ) return "<p>$template_name: Template doesn't exist</p>";
    ob_start();
    if( $this->is_dev() ) echo "<!-- Template Path: $path -->";
    include( $path );
    return ob_get_clean();
  }

  /**
   * Delete deprecated plugins
   *
   * @return void
   */
  public function delete_deprecated_plugins() {
    foreach( $this->deprecated_plugins as $id => $plugin_slug ) {
      $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug;
      if( file_exists($plugin_file) ) {
        $plugin_data = get_plugin_data($plugin_file);
        if( delete_plugins([$plugin_slug]) ) {
          $this->add_admin_notice("plugin-deleted-$id", "[RH SEO] Deleted deprecated plugin „{$plugin_data['Name']}“.", "success");
        }
      }
    }
    // 
  }

  /**
   * Adds an admin notice
   *
   * @param string $key
   * @param string $message
   * @param string $type
   * @return void
   */
  public function add_admin_notice( $key, $message, $type = 'warning', $is_dismissible = false ) {
    $notices = get_transient("$this->prefix-admin-notices");
    if( !$notices ) $notices = [];
    $notices[$key] = [
      'message' => $message,
      'type' => $type,
      'is_dismissible' => $is_dismissible
    ];
    set_transient("$this->prefix-admin-notices", $notices);
  }
  
  /**
   * Shows admin notices from transient
   *
   * @return void
   */
  public function show_admin_notices() {
    $notices = get_transient("$this->prefix-admin-notices");
    delete_transient("$this->prefix-admin-notices");
    if( !is_array($notices) ) return;
    foreach( $notices as $notice ) {
      ob_start() ?>
      <div class="notice notice-<?= $notice['type'] ?> <?= $notice['is_dismissible'] ? 'is-dismissible' : '' ?>">
        <p><?= $notice['message'] ?></p>
      </div>
      <?php echo ob_get_clean();
    }
  }

}
/**
 * Initialize main class
 */
SEO::getInstance();

/**
 * Make AdminUtils instance available API calls
 *
 * @return SEO
 */
function seo() { 
  return SEO::getInstance(); 
}
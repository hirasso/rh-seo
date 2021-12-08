<?php
/**
 * Plugin Name: RH SEO
 * Version: 1.1.5
 * Author: Rasso Hilber
 * Description: Lightweight SEO optimizations for WordPress
 * Author URI: https://rassohilber.com
**/

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
* Require Modules
*/
require_once(__DIR__ . '/inc/class.field-groups.php');
require_once(__DIR__ . '/inc/class.meta-tags.php');
require_once(__DIR__ . '/inc/class.yoast-compatibility.php');
require_once(__DIR__ . '/inc/class.xml-sitemaps.php');

/**
 * Main Class
 */
class SEO {

  public $prefix = 'rhseo';

  private $deprecated_plugins = [
    'wordpress-seo/wp-seo.php'
  ];

  /**
   * Initialize function
   *
   * @return void
   */
  public function initialize() {
    add_action('admin_notices', [$this, 'maybe_show_notice_acf_missing'], 9);
    add_action('admin_notices', [$this, 'show_notices'] );

    if( !defined('ACF') ) return;

    $this->init_plugin_modules();
    add_action('admin_init', [$this, 'admin_init'], 11);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_styles'] );
    add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts'], 100 );
    add_action('plugins_loaded', [$this, 'load_textdomain']);
    add_action('template_redirect', [$this, 'redirect_attachment_pages']);
    add_action('admin_init', [$this, 'delete_tagline']);
  }

  /**
   * Init plugin modules
   *
   * @return void
   */
  private function init_plugin_modules() {
    // must be initialized first, to make fields available
    new Field_Groups();
    new MetaTags();
    // new Yoast_Compatibility(); // deactivated, Yoast keeps breaking sites badly
    new XML_Sitemaps();
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
   * @param string $path
   * @return string
   */
  private function asset_uri( string $path ): string {
    $uri = plugins_url( $path, __FILE__ );
    $file = $this->get_file_path( $path );
    if( file_exists( $file ) ) {
      $version = filemtime( $file );
      $uri .= "?v=$version";
    }
    return $uri;
  }

  /**
   * Helper function to get a file path inside this plugin's folder
   *
   * @return string
   */
  public function get_file_path( string $path ): string {
    $path = ltrim( $path, '/' );
    $file = plugin_dir_path( __FILE__ ) . $path;
    return $file;
  }

  /**
   * enqueue styles
   *
   * @return void
   */
  public function enqueue_styles() {
    wp_enqueue_style('rhseo', $this->asset_uri('assets/rhseo.css'));
  }

  /**
   * enqueue styles
   *
   * @return void
   */
  public function enqueue_scripts() {
    wp_enqueue_script('rhseo', $this->asset_uri('assets/rhseo.js'), [], false, true);
  }

  /**
   * Helper function to transform an array to an object
   *
   * @param array $array
   * @return stdClass
   */
  public function to_object( $array ) {
    return json_decode(json_encode($array));
  }

  /**
   * Helper function to detect a development environment
   */
  private function is_dev() {
    return defined('\WP_ENV') && \WP_ENV === 'development';
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
    $path = $this->get_file_path("templates/$template_name.php");
    $path = apply_filters("rhseo/template/$template_name", $path);
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
          $this->add_notice("plugin-deleted-$id", "[RH SEO] Deleted deprecated plugin „{$plugin_data['Name']}“.", "success");
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
  public function add_notice( $key, $message, $type = 'warning', $is_dismissible = false ) {
    $notices = get_transient($this->get_notices_transient_name());
    if( !$notices ) $notices = [];
    $notices[$key] = [
      'message' => $message,
      'type' => $type,
      'is_dismissible' => $is_dismissible
    ];
    set_transient($this->get_notices_transient_name(), $notices);
  }

  /**
   * Shows an admin notice if ACF is not installed
   *
   * @return void
   * @author Rasso Hilber <mail@rassohilber.com>
   */
  public function maybe_show_notice_acf_missing(): void {
    if( defined('ACF') ) return;
    $message = wp_sprintf(
      __("RHSEO requires the plugin %s to be installed and activated.", 'acfml'),
      '<a href="https://www.advancedcustomfields.com/" target="_blank">Advanced Custom Fields</a>',
    );
    $this->add_notice('acf-missing', $message, 'error');
  }
  
  /**
   * Shows admin notices from transient
   *
   * @return void
   */
  public function show_notices() {
    $notices = get_transient($this->get_notices_transient_name()) ?: [];
    delete_transient($this->get_notices_transient_name());
    foreach( $notices as $notice ) {
      ob_start() ?>
      <div class="notice notice-<?= $notice['type'] ?> <?= $notice['is_dismissible'] ? 'is-dismissible' : '' ?>">
        <p><?= $notice['message'] ?></p>
      </div>
      <?php echo ob_get_clean();
    }
  }

  /**
   * Get the notices transient name for the current user
   *
   * @return string
   */
  private function get_notices_transient_name(): string {
    $user_id = get_current_user_id();
    return "rhseo-admin-notices-$user_id";
  }

  /**
   * load_textdomain
   *
   * Loads the plugin's translated strings similar to load_plugin_textdomain().
   *
   * @param	string $locale The plugin's current locale.
   * @return	void
   */
  public function load_textdomain() {

    $domain = 'rhseo';
    /**
     * Filters a plugin's locale.
     *
     * @date	8/1/19
     * @since	5.7.10
     *
     * @param 	string $locale The plugin's current locale.
     * @param 	string $domain Text domain. Unique identifier for retrieving translated strings.
     */
    $locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
    $mofile = "$locale.mo";

    // Try to load from the languages directory first.
    if( load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $mofile ) ) {
      return true;
    }

    // Load from plugin lang folder.
    return load_textdomain( $domain, $this->get_file_path( 'lang/' . $mofile ) );
  }

  /**
   * Redirect Attachment Pages to the actual file
   *
   * @return void
   */
  public function redirect_attachment_pages() {
    if( !apply_filters('rhseo/redirect_attachment_pages', true ) ) return;
    if( !is_attachment() ) return;
    if( !$object_id = get_queried_object_id() ) return;
    $url = wp_get_attachment_url( $object_id );
    wp_redirect( $url, 301 );
    exit;
  }

  /**
   * Get a field
   *
   * @param string $name
   * @return mixed
   */
  public function get_field($name, $post_id = 0) {
    $value = \get_field("rhseo_{$name}", $post_id);
    return $value;
  }

  /**
   * Gets a field from the SEO options page
   *
   * @param string $name
   * @return mixed
   * @author Rasso Hilber <mail@rassohilber.com>
   */
  public function get_global_options_field(string $name) {
    return $this->get_field($name, $this->get_options_page_slug());
  }

  /**
   * checks if an object is set to noindex
   *
   * @param null|WP_Post|WP_Term $object
   * @return boolean
   */
  public function object_is_set_to_noindex( $object = null ): bool {
    if( is_a($object, "WP_Post") || is_a($object, "WP_Term") ) {
      return (bool) $this->get_field("noindex", $object);
    }
    return false;
  }

  /**
   * Returns the queried object or the front page
   *
   * @return null|object
   */
  public function get_queried_object(): ?object {
    return apply_filters('rhseo/queried_object', get_queried_object());
  }

  /**
   * Undocumented function
   *
   * @return boolean
   */
  public function is_front_page(): bool {
    return apply_filters('rhseo/is_front_page', is_front_page());
  }

  /**
   * Checks if polylang is enabled
   *
   * @return null|object
   */
  public function get_polylang_languages(): ?object {
    $languages = null;
    if( !defined('POLYLANG_VERSION') ) return $languages;
    return seo()->to_object(\pll_the_languages([
      'echo' => 0,
      'raw' => 1,
      'hide_if_empty' => false
    ]));
  }

  /**
   * Retrieve the options page slug
   *
   * @return string
   */
  public function get_options_page_slug(): string {
    $slug = "rhseo-options";
    if( function_exists('\pll_current_language') ) {
      $slug = "rhseo-options--" . \pll_current_language();
    }
    return $slug;
  }

  /**
   * Deletes the default Blog Tagline
   *
   * @return void
   * @author Rasso Hilber <mail@rassohilber.com>
   */
  public function delete_tagline(): void {
    delete_option('blogdescription');
  }
}

/**
 * Initialize the $seo instance
 *
 * @return SEO
 */
function seo() {
  static $instance;
  if( !isset($instance) ) {
    $instance = new SEO();
    $instance->initialize();
  }
  return $instance;
}
seo();
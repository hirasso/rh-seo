<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Compatibility for YOAST SEO
 */
class YOASTCompatibility extends Singleton {
  
  private $prefix;

  public function __construct(SEO $seo) {
    add_action( 'plugins_loaded', [$this, 'disable_yoast_seo_frontend'] );
    add_action( 'add_meta_boxes', [$this, 'meta_boxes'], 11 );
  }

  /**
   * Disable the YOAST frontend.
   *
   * @return void
   */
  public function disable_yoast_seo_frontend() {
    if( is_admin() || !defined('WPSEO_VERSION') ) return;
    $loader = \YoastSEO()->classes->get( \Yoast\WP\SEO\Loader::class );
    \remove_action( 'init', [ $loader, 'load_integrations' ] );
    \remove_action( 'rest_api_init', [ $loader, 'load_routes' ] );
  }

  /**
   * Remove the YOAST Meta Box everywhere
   *
   * @return void
   */
  public function meta_boxes() {
    remove_meta_box( 'wpseo_meta', null, 'normal' );
    remove_meta_box( 'wpseo_meta', null, 'side' );
  }

}

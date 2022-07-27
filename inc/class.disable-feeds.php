<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class DisableFeeds {
  
  private $prefix;

  public function __construct() {
    $this->prefix = seo()->prefix;
    add_action('init', [$this, 'init']);
  }

  /**
   * Runs on init hook. allows the theme to disable the disabling of feeds :)
   *
   * @return void
   */
  public function init(): void {
    
    if( !apply_filters('rhseo/disable_feeds', true) ) return;
    
    remove_action( 'wp_head', 'feed_links_extra', 3 );
    remove_action( 'wp_head', 'feed_links', 2 );

    add_action('wp', [$this, 'redirect_feeds']);

  }

  /**
   * Redirect all feed URLs to their appropriate location
   *
   * @return void
   */
  public function redirect_feeds(): void {
    global $wp_query, $post_type;
    if( !is_feed() || is_admin() ) return;
    
    $url = home_url('/');
    if( !empty($post_type) ) $url = get_post_type_archive_link($post_type);
    
    wp_safe_redirect($url, 301);
    exit;
  }

  
}

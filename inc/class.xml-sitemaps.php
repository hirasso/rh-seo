<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sitemaps
 */
class XML_Sitemaps {
  

  public function __construct() {
    add_filter('wp_sitemaps_add_provider', [$this, 'sitemaps_providers'], 10, 2);
    add_filter('wp_sitemaps_taxonomies', [$this, 'sitemaps_taxonomies']);
    add_filter('wp_sitemaps_post_types', [$this, 'sitemaps_post_types']);
  }

  /**
   * Filter XML Sitemaps providers
   *
   * @param string $provider
   * @param string $name
   * @return void
   */
  public function sitemaps_providers($provider, $name) {
    if ( 'users' === $name ) return false;
    return $provider;
  }

  /**
   * Filter XML Sitemaps taxonomies
   *
   * @param array $taxonomies
   * @return array
   */
  public function sitemaps_taxonomies($taxonomies): array {
    unset($taxonomies['category']);
    unset($taxonomies['post_tag']);
    return $taxonomies;
  }

  /**
   * Filter XML Sitemaps post types
   *
   * @param array $post_types
   * @return array
   */
  public function sitemaps_post_types($post_types): array {
    unset($post_types['post']);
    return $post_types;
  }

}

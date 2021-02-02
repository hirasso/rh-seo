<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sitemaps
 */
class XML_Sitemaps {
  
  private $prefix;

  public function __construct() {
    $this->prefix = seo()->prefix;
    add_filter('wp_sitemaps_add_provider', [$this, 'sitemaps_providers'], 10, 2);
    add_filter('wp_sitemaps_taxonomies', [$this, 'sitemaps_taxonomies']);
    add_filter('wp_sitemaps_post_types', [$this, 'sitemaps_post_types']);
    add_filter('wp_sitemaps_posts_query_args', [$this, 'sitemaps_posts_query_args'], 10, 2);
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

  /**
   * Alter sitemap query args
   *
   * @param array $args
   * @param [type] $post_type
   * @return array
   */
  public function sitemaps_posts_query_args( $args, $post_type ): array {
    $meta_query = $args['meta_query'] ?? [];
    $meta_query[] = [
      'relation' => 'OR',
      [
        'key' => "{$this->prefix}_noindex",
        'value' => 1,
        'compare' => '!=',
        'type' => 'NUMERIC'
      ],
      [
        'key' => "{$this->prefix}_noindex",
        'compare' => 'NOT EXISTS'
      ],
    ];
    $args['meta_query'] = $meta_query;
    return $args;
  } 

}

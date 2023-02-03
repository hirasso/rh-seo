<?php

namespace R\SEO;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Sitemaps
 */
class XML_Sitemaps
{

  private $prefix;

  public function __construct()
  {
    $this->prefix = seo()->prefix;
    add_action('init', [$this, 'add_qtranslate_sitemaps_provider']);
    add_action('registered_taxonomy', [$this, 'registered_taxonomy'], 10, 3);
    add_filter('wp_sitemaps_add_provider', [$this, 'sitemaps_providers'], 10, 2);
    add_filter('wp_sitemaps_taxonomies', [$this, 'sitemaps_taxonomies']);
    add_filter('wp_sitemaps_post_types', [$this, 'sitemaps_post_types']);
    add_filter('wp_sitemaps_posts_query_args', [$this, 'inject_meta_query_noindex']);
    add_filter('wp_sitemaps_taxonomies_query_args', [$this, 'inject_meta_query_noindex']);
    add_action('save_post', [$this, 'ensure_post_has_noindex_value']);
    add_filter('wp_sitemaps_stylesheet_css', [$this, 'wp_sitemaps_stylesheet_css']);
  }

  /**
   * Filter XML Sitemaps providers
   *
   * @param string $provider
   * @param string $name
   * @return void
   */
  public function sitemaps_providers($provider, $name)
  {
    if ('users' === $name) return false;
    return $provider;
  }

  /**
   * Filter XML Sitemaps taxonomies
   *
   * @param array $taxonomies
   * @return array
   */
  public function sitemaps_taxonomies($taxonomies): array
  {
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
  public function sitemaps_post_types($post_types): array
  {
    unset($post_types['post']);
    return $post_types;
  }

  /**
   * Alter sitemap query args to exclude items (posts or terms) with 'noindex' set to true
   *
   * @param array $args
   * @return array
   */
  public function inject_meta_query_noindex($args): array
  {
    $meta_query = $args['meta_query'] ?? [];
    $meta_query[] = [
      'relation' => 'AND',
      [
        'key' => "{$this->prefix}_noindex",
        'value' => 1,
        'compare' => '!=',
        'type' => 'NUMERIC'
      ],
    ];
    $args['meta_query'] = $meta_query;
    return $args;
  }

  /**
   * Perform adjustments on taxonomies (Set taxonomies with object type 'attachment' to private)
   *
   * @param string $taxonomy
   * @param string|array $object_type
   * @param array $taxonomy_arr
   * @return void
   */
  public function registered_taxonomy($taxonomy, $object_type, $taxonomy_arr)
  {
    global $wp_taxonomies;
    if (
      $object_type === 'attachment' ||
      (is_array($object_type) && count($object_type) === 1 && $object_type[0] === 'attachment')
    ) {
      $wp_taxonomies[$taxonomy]->public = false;
    }
  }

  /**
   * Adds a custom provider to the WordPress sitemap:
   *    - adds an entry 'wp-sitemap-languages-1.xml' to the sitemap index for the default language
   *    - adds urls to the sitemaps for each language to the new sitemap 'wp-sitemap-languages-1.xml'
   *
   * @return void
   */
  public function add_qtranslate_sitemaps_provider()
  {
    if (!defined('QTX_VERSION')) return;

    $default_language = qtranxf_getLanguageDefault();
    $current_language = qtranxf_getLanguage();
    // we only want to add the custom provider for the default language
    if ($current_language !== $default_language) return;

    // registers the new provider for the sitemap
    require_once(RHSEO_DIR . '/inc/class.qtranslate-xt-sitemaps-provider.php');
    $provider = new Qtranslate_XT_Sitemaps_Provider();
    wp_register_sitemap_provider('languages', $provider);
  }

  /**
   * Make sure a post contains a "rhseo_noindex" value
   *
   * @param integer $post_id
   * @return void
   */
  public function ensure_post_has_noindex_value(int $post_id): void {
    $noindex_key = "{$this->prefix}_noindex";
    $noindex = get_post_meta($post_id, $noindex_key, true);
    // If noindex is empty, set it to 0
    if ($noindex === "") update_post_meta($post_id, $noindex_key, 0);
  }

  /**
   * Hides the WordPress Sitemap description visually
   *
   * @param string $css
   * @return string
   */
  public function wp_sitemaps_stylesheet_css(string $css): string
  {
      ob_start() ?>
      #sitemap__header p {
        display: none;
      }
  <?php $append = ob_get_clean();
      return $css . $append;
  }
}

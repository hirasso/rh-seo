<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * SEO Meta Tags
 */
class MetaTags {

  public function __construct() {

    add_action('wp_head', [$this, 'wp_head'], 4);
    add_filter('pre_option_blogname', [$this, 'filter_blogname']);
    add_filter('pre_option_blogdescription', [$this, 'filter_blogdescription']);
    add_filter('document_title_parts', [$this, 'document_title_parts']);
    add_action('wp', [$this, 'init']);
  }

  public function init() {
    if( is_admin() ) return;
    $is_noindex = seo()->object_is_set_to_noindex(seo()->get_queried_object());
    if( $is_noindex ) add_filter('wp_robots', 'wp_robots_no_robots');
  }

  /**
   * Inject SEO meta tags
   *
   * @return void
   */
  public function wp_head() {
    // allow to disable meta tags
    if( !apply_filters('rhseo/render_meta_tags', true ) ) return;
    
    ob_start() ?>

<!-- SEO: Start -->
<meta property="og:title" content="<?= wp_get_document_title() ?>" />
<?php if($description = get_bloginfo('description')) : ?>
<meta name="description" content="<?= esc_attr($description) ?>" />
<meta property="og:description" content="<?= esc_attr($description) ?>" />
<?php endif; ?>
<meta property="og:locale" content="<?= esc_attr($this->get_locale()) ?>" />
<meta property="og:type" content="website" />
<meta property="og:url" content="<?= esc_attr($this->get_current_url()) ?>" />
<meta property="og:site_name" content="<?= esc_attr(get_bloginfo('name')) ?>" />
<?php if($image = $this->get_og_image_url()): ?>
<meta property="og:image" content="<?= esc_attr($image) ?>" />    
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image" />
<!-- SEO: End -->

    <?php echo ob_get_clean();
  }

  /**
   * Get current URL
   *
   * @return [string] $url
   */
  private function get_current_url( $path = '' ) {
    $url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
    return $url;
  }

  /**
   * Get locale for tag
   *
   * @return string Locale
   */
  private function get_locale() {
    $lang = get_bloginfo( 'language' );
    return str_replace('-', '_', $lang);
  }

  /**
   * Filter document title parts
   *
   * @return void
   */
  public function document_title_parts($parts) {
    $parts['title'] = $this->get_seo_value('document_title');
    if( !empty($parts['tagline']) ) {
      $parts['tagline'] = seo()->get_field('description', 'rhseo-options');
    }
    
    return $parts;
  }

  /**
   * Filters get_bloginfo('name')
   *
   * @param string $name
   * @return string|bool
   */
  public function filter_blogname( $value ) {
    remove_filter('pre_option_blogname', [$this, 'filter_blogname']);
    if( $custom_site_name = seo()->get_field('site_name', 'rhseo-options') ) {
      $value = __($custom_site_name);
    }
    add_filter('pre_option_blogname', [$this, 'filter_blogname']);
    return $value;
  }

  /**
  * Filters get_bloginfo('description')
  *
  * @param string $name
  * @return string|bool
  */
  public function filter_blogdescription( $value ) {
    remove_filter('pre_option_blogdescription', [$this, 'filter_blogdescription']);
    $value = $this->get_seo_value('description');
    add_filter('pre_option_blogdescription', [$this, 'filter_blogdescription']);
    return $value;
  }

  /**
   * Get an SEO value, fall back to the global default
   *
   * @param string $name
   * @return void
   */
  public function get_seo_value( string $name ) {
    
    global $wp_query;
    $value = null;
    if( !isset($wp_query) ) return $value;
    $queried_object= $wp_query->get_queried_object();
    if( $queried_object) $value = seo()->get_field($name, $this->get_acf_post_id($queried_object));
    // fallbacks for document_title
    if( !$value && $name === 'document_title' ) {
      if( $queried_object instanceof \WP_Post ) $value = get_the_title($queried_object->ID);
      if( $queried_object instanceof \WP_Post_Type ) $value = $queried_object->labels->name;
      if( $queried_object instanceof \WP_Term ) $value = $queried_object->name;
    }
    // fallbacks for 'image'
    if( !$value && $name === 'image' ) {
      if( $queried_object instanceof \WP_Post ) $value = get_post_thumbnail_id($queried_object->ID);
    }
    // finally fall back to global options
    if( !$value ) $value = seo()->get_field($name, 'rhseo-options');
    return $value;
  }

  /**
   * Get the acf post id for different WP Objects
   *
   * @param mixed $queried_object
   * @return mixed
   */
  private function get_acf_post_id( $queried_object) {
    $post_id = null;
    if( $queried_object instanceof \WP_Post ) {
      $post_id = $queried_object;
    } elseif( $queried_object instanceof \WP_Term ) {
      $post_id = $queried_object;
    } elseif( $queried_object instanceof \WP_Post_Type ) {
      $post_id = "rhseo-options--$queried_object->name";
    }
    return $post_id;
  }

  /**
   * Filter document title separator
   *
   * @param [type] $sep
   * @return void
   */
  public function document_title_separator( $sep ) {
    return '—';
  }

  /**
   * Get OG Image
   *
   * @return mixed
   */
  private function get_og_image_url() {

    $value = $this->get_seo_value('image');

    // bail early if empty
    if( empty($value) ) return $value;

    // get the URL
    $value = wp_get_attachment_url($value['ID'] ?? $value);

    return $value;
  }

}

<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * SEO Meta Tags
 */
class MetaTags {

  public function __construct() {
    add_action('wp', [$this, 'init']);
  }

  public function init() {
    add_action('wp_head', [$this, 'wp_head'], 4);
    add_filter('pre_option_blogname', [$this, 'filter_blogname']);
    add_filter('pre_option_blogdescription', [$this, 'filter_blogdescription']);
    add_filter('document_title_parts', [$this, 'document_title_parts']);
    $this->adjust_robots();
  }

  /**
   * Adjust wp_robots to add noindex for selected objects
   *
   * @return void
   */
  public function adjust_robots() {
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
    if( !seo()->is_front_page() ) {
      $parts['site'] = get_bloginfo( 'name', 'display' );
      unset($parts['tagline']);
    }
    if( !empty($parts['tagline']) ) {
      $parts['tagline'] = seo()->get_field('description', seo()->get_options_page_slug());
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
    if( $custom_site_name = seo()->get_field('site_name', seo()->get_options_page_slug()) ) {
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
   * @param $object
   * @return void
   */
  public function get_seo_value( string $name, $object = null ) {
    
    if( empty($object) ) $object = seo()->get_queried_object();
    
    if( $object ) $value = seo()->get_field($name, $this->get_acf_post_id($object));
    
    // fallbacks for document_title
    if( empty($value) && $name === 'document_title' ) {
      if( $object instanceof \WP_Post ) $value = get_the_title($object->ID);
      if( $object instanceof \WP_Post_Type ) $value = $object->labels->name;
      if( $object instanceof \WP_Term ) $value = $object->name;
      if( !$object ) $value = get_bloginfo('name');
    }
    // fallbacks for 'image'
    if( empty($value) && $name === 'image' ) {
      if( $object instanceof \WP_Post ) $value = get_post_thumbnail_id($object->ID);
    }
    // finally fall back to global options
    if( empty($value) ) $value = seo()->get_field($name, 'rhseo-options');
    /**
     * Allow themes to filter SEO values
     */
    $value = apply_filters("rhseo/get_seo_value/name=$name", $value, $object);
    return $value ?? null;
  }

  /**
   * Get the acf post id for different WP Objects
   *
   * @param mixed $object
   * @return mixed
   */
  private function get_acf_post_id( $object) {
    $post_id = null;
    if( $object instanceof \WP_Post ) {
      $post_id = $object;
    } elseif( $object instanceof \WP_Term ) {
      $post_id = $object;
    } elseif( $object instanceof \WP_Post_Type ) {
      $post_id = seo()->get_options_page_slug() . "--$object->name";
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

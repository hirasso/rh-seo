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
  }

  /**
   * Inject SEO meta tags
   *
   * @return void
   */
  public function wp_head() {
    ob_start() ?>

<!-- SEO: Start -->
<?php if( seo()->object_is_set_to_noindex(get_queried_object()) ) wp_no_robots(); ?>
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
    $title = $this->get_seo_value('document_title');
    $parts['title'] = $title;
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
    $value = null;
    $qo = get_queried_object();
    if( $qo ) $value = seo()->get_field($name, $this->get_acf_post_id($qo));
    // fallbacks for document_title
    if( !$value && $name === 'document_title' ) {
      if( $qo instanceof \WP_Post ) $value = get_the_title($qo->ID);
      if( $qo instanceof \WP_Post_Type ) $value = $qo->labels->name;
      if( $qo instanceof \WP_Term ) $value = $qo->name;
    }
    // fallbacks for 'description'
    if( !$value && $name === 'description' ) {
      if( $qo instanceof \WP_Post ) $value = get_the_excerpt($qo->ID);
      if( $qo instanceof \WP_Term ) $value = $qo->description;
    }
    // fallbacks for 'image'
    if( !$value && $name === 'image' ) {
      if( $qo instanceof \WP_Post ) $value = get_post_thumbnail_id($qo->ID);
    }
    // finally fall back to global options
    if( !$value ) $value = seo()->get_field($name, 'rhseo-options');
    return $value;
  }

  /**
   * Get the acf post id for different WP Objects
   *
   * @param mixed $qo
   * @return mixed
   */
  private function get_acf_post_id( $qo ) {
    $post_id = null;
    if( $qo instanceof \WP_Post ) {
      $post_id = $qo->ID;
    } elseif( $qo instanceof \WP_Term ) {
      $post_id = $qo;
    } elseif( $qo instanceof \WP_Post_Type ) {
      $post_id = "rhseo-options--$qo->name";
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

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
<meta property="og:locale" content="<?= $this->get_locale() ?>" />
<meta property="og:type" content="website" />
<meta property="og:title" content="<?= wp_get_document_title() ?>" />
<meta property="og:url" content="<?= $this->get_current_url() ?>" />
<meta property="og:site_name" content="<?= get_bloginfo('name') ?>" />
<?php if($description = get_bloginfo('description')) : ?>
<meta property="og:description" content="<?= $description ?>" />
<?php endif; ?>
<?php if($image = $this->get_og_image_url()): ?>
<meta property="og:image" content="<?= $image ?>" />    
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image" />
<meta name="description" content="<?= $description ?>" />
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
  public function document_title_parts($title) {
    $qo = get_queried_object();
    if( is_tax() ) {
      $title['title'] = $this->get_term_title($qo);
    }
    return $title;
  }

  /**
   * Get the title for a term
   *
   * @param \WP_Term $term
   * @return void
   */
  private function get_term_title($term): string {
    $value = seo()->get_field('title', $term);
    if( !$value ) $value = $term->name;
    return $value;
  }

  /**
  * Filters get_bloginfo('description')
  *
  * @param string $name
  * @return string|bool
  */
  public function filter_blogdescription( $value ) {
    $value = false;
    $qo = get_queried_object();
    if( $qo instanceof \WP_Post ) {
      $value = seo()->get_field('description', $qo->ID);
    } elseif( $qo instanceof \WP_Term ) {
      $value = seo()->get_field('description', $qo);
    }
    if( !$value ) $value = seo()->get_field('description', 'options');
    return $value;
  }

  /**
   * Filters get_bloginfo('name')
   *
   * @param string $name
   * @return string|bool
   */
  public function filter_blogname( $value ) {
    if( $custom_title = seo()->get_field('site_name', 'options') ) {
      $value = __($custom_title);
    }
    return $value;
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
    $value = false;

    $qo = get_queried_object();
    if( $qo instanceof \WP_Post ) {
      $value = seo()->get_field("image", $qo->ID, false);
      // ... then try post thumbnail
      if( !$value ) $value = get_post_thumbnail_id($qo->ID);
    } if( $qo instanceof \WP_Term ) {
      $value = seo()->get_field("image", $qo);
    }

    // finally try global setting
    if( !$value ) $value = seo()->get_field("image", 'options', false);

    // bail early if empty
    if( empty($value) ) return $value;

    $value = wp_get_attachment_url($value['ID'] ?? $value);

    return $value;
  }

}

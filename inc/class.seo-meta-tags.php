<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * SEO Meta Tags
 */
class SEOMetaTags extends Singleton {
  
  private $prefix;

  public function __construct($prefix) {

    $this->prefix = $prefix;

    add_action('acf/init', [$this, 'acf_init']);
    add_action('wp_head', [$this, 'wp_head'], 4);
    
    add_filter('pre_option_blogname', [$this, 'filter_blogname']);
    add_filter('pre_option_blogdescription', [$this, 'filter_blogdescription']);
    add_filter("acf/prepare_field/name={$this->prefix}_site_name", [$this, 'maybe_hide_site_name']);
    // add_filter('document_title_parts', [$this, 'document_title_parts']);
    // add_filter('document_title_separator', [$this, 'document_title_separator']);
  }

  /**
   * Inject SEO meta tags
   *
   * @return void
   */
  public function wp_head() {
    ob_start() ?>

<!-- RH SEO: Start -->
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
<!-- RH SEO: End -->

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
    return $parts;
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
      $value = $this->get_field('description', $qo->ID);
    }
    if( !$value ) $value = $this->get_field('description', 'options');
    return $value;
  }

  /**
   * Filters get_bloginfo('name')
   *
   * @param string $name
   * @return string|bool
   */
  public function filter_blogname( $value ) {
    if( $custom_title = $this->get_field('site_name', 'options') ) {
      return $custom_title;
    }
    return $value;
  }


  /**
   * Get options field
   *
   * @param string $name
   * @return mixed
   */
  private function get_field($name, $post_id = 0, $format_value = true) {
    return get_field("{$this->prefix}_{$name}", $post_id, $format_value);
  }

  /**
   * Inits Admin
   *
   * @return void
   */
  public function acf_init() {
    $this->add_options_page();
  }

  /**
   * Add SEO Options Page
   *
   * @return void
   */
  private function add_options_page() {
    acf_add_options_page([
      'page_title' => 'SEO Options',
      'menu_slug' => "{$this->prefix}-options",
      'position' => '59.6',
      'icon_url' => 'dashicons-share'
    ]);

    acf_add_local_field_group([
      'key' => "group_{$this->prefix}_options",
      'title' => __('Search engine optimization'),
      'fields' => [
        [ 
          'key' => "key_{$this->prefix}_site_name",
          'name' => "{$this->prefix}_site_name",
          'type' => 'text',
          'label' => __('Site Name'),
        ],
        [ 
          'key' => "key_{$this->prefix}_description",
          'name' => "{$this->prefix}_description",
          'type' => 'text',
          'label' => __('Description'),
          'required' => false,
          'instructions' => 'Optimal length: 50–160 characters',
          'max' => 200
        ],
        [ 
          'key' => "key_{$this->prefix}_image",
          'name' => "{$this->prefix}_image",
          'type' => 'image',
          'label' => __('Image'),
          'required' => false,
          'instructions' => 'Used for facebook or twitter.',
        ],
      ],
      'location' => [
        [
          [
            'param' => 'options_page',
            'operator' => '==',
            'value' => "{$this->prefix}-options",
          ],
        ],
        [
          [
            'param' => 'post_type',
            'operator' => '==',
            'value' => "post",
          ],
        ],
        [
          [
            'param' => 'post_type',
            'operator' => '!=',
            'value' => "post",
          ],
        ],
      ],
      'menu_order' => 100,
      'position' => 'normal',
      'active' => true,
    ]);
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
      // first try seo 'rh_seo_image'
      $value = get_field("{$this->prefix}_image", $qo->ID, false);
      // ... then try post thumbnail
      if( !$value ) $value = get_post_thumbnail_id($qo->ID);
    }

    // finally try global setting
    if( !$value ) $value = get_field("{$this->prefix}_image", 'options', false);

    // get url for value if set
    if( !empty($value) ) $value = wp_get_attachment_url($value);

    return $value;
  }

  /**
   * Hides site_name on non-options pages
   *
   * @param [type] $field
   * @return void
   */
  public function maybe_hide_site_name( $field ) {
    global $pagenow;
    if( $pagenow !== 'admin.php' ) return false;
    if( ($_GET['page'] ?? false) !== "{$this->prefix}_options") return false;
    return $field;
  }
}

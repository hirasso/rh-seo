<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * SEO Meta Tags
 */
class MetaTags extends Singleton {
  
  private $prefix;

  public function __construct(SEO $seo) {

    $this->prefix = $seo->prefix;

    add_action('acf/init', [$this, 'acf_init'], 10);
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
    $value = $this->get_field('title', $term);
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
      $value = $this->get_field('description', $qo->ID);
    } elseif( $qo instanceof \WP_Term ) {
      $value = $this->get_field('description', $qo);
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
      $value = __($custom_title);
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
    $this->add_admin_ui();
  }

  /**
   * Add SEO Options Page
   *
   * @return void
   */
  private function add_admin_ui() {
    
    $fields = ['text', 'textarea', 'image'];
    $field_types = [];
    foreach( $fields as $key ) {
      $field_type = $this->is_qtranslate_enabled() ? "qtranslate_$key" : $key;
      $field_types[$key] = array_merge( acf_get_field_type($field_type)->defaults, ['type' => $field_type]);
    }
    
    acf_add_options_page([
      'page_title' => __('SEO Options', 'rhseo'),
      'menu_slug' => "{$this->prefix}-options",
      'position' => '59.6',
      'icon_url' => 'dashicons-share'
    ]);

    $fields = [
      array_merge($field_types['textarea'], [ 
        'key' => "key_{$this->prefix}_description",
        'name' => "{$this->prefix}_description",
        'label' => $this->is_options_page() ? __('Site Description', 'rhseo') : __('Description', 'rhseo'),
        'required' => false,
        'instructions' => __('Optimal length: 50–160 characters', 'rhseo'),
        'max' => 200,
        'rows' => 2,
        'acfml_multilingual' => true,
      ]),
      array_merge($field_types['image'], [ 
        'key' => "key_{$this->prefix}_image",
        'name' => "{$this->prefix}_image",
        'label' => __('Preview Image', 'rhseo'),
        'required' => false,
        'instructions' => __('For services like Facebook or Twitter.', 'rhseo'),
        'acfml_multilingual' => true,
      ])
    ];

    if( $this->is_options_page() ) {
      $fields = array_merge([
        array_merge($field_types['text'], [ 
          'key' => "key_{$this->prefix}_site_name",
          'name' => "{$this->prefix}_site_name",
          'label' => __('Site Name', 'rhseo'),
          'acfml_multilingual' => true
        ]),
      ], $fields);
    } else {
      $fields = array_merge([
        array_merge($field_types['text'], [ 
          'key' => "key_{$this->prefix}_document_title",
          'name' => "{$this->prefix}_document_title",
          'instructions' => __('Optional. Overwrites the title.', 'rhseo'),
          'label' => __('Document Title', 'rhseo'),
          'acfml_multilingual' => true
        ]),
      ], $fields);
    }

    acf_add_local_field_group([
      'key' => "group_{$this->prefix}_options",
      'title' => $this->is_options_page() ? __('SEO Global Defaults', 'rhseo') : __('SEO', 'rhseo'),
      'fields' => $fields,
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
        [
          [
            'param' => 'taxonomy',
            'operator' => '==',
            'value' => "all",
          ],
        ],
      ],
      'menu_order' => 1000,
      'position' => 'normal',
      'active' => true,
    ]);
  }

  /**
   * Checks if qtranslate is enabled
   *
   * @return boolean
   */
  private function is_qtranslate_enabled() {
    return defined('QTX_VERSION');
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
      $value = $this->get_field("image", $qo->ID, false);
      // ... then try post thumbnail
      if( !$value ) $value = get_post_thumbnail_id($qo->ID);
    } if( $qo instanceof \WP_Term ) {
      $value = $this->get_field("image", $qo);
    }

    // finally try global setting
    if( !$value ) $value = $this->get_field("image", 'options', false);

    // bail early if empty
    if( empty($value) ) return $value;

    $value = wp_get_attachment_url($value['ID'] ?? $value);

    return $value;
  }

  /**
   * Detects the options page
   *
   * @return bool
   */
  public function is_options_page(): bool {
    global $pagenow;
    if( $pagenow !== 'admin.php' ) return false;
    if( ($_GET['page'] ?? false) !== "{$this->prefix}-options") return false;
    return true;
  }
}

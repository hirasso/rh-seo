<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * SEO Meta Tags
 */
class Admin_UI {
  
  private $prefix;

  public function __construct() {
    $this->prefix = seo()->prefix;
    add_action('acf/init', [$this, 'acf_init'], 10);
  }

  /**
   * Add SEO Options Page
   *
   * @return void
   */
  public function acf_init() {
    
    $this->add_options_page();
    $this->add_field_group();
    
  }

  /**
   * Adds the options page
   *
   * @return void
   */
  private function add_options_page() {
    acf_add_options_page([
      'page_title' => __('SEO Options', 'rhseo'),
      'menu_slug' => "{$this->prefix}-options",
      'position' => '59.6',
      'icon_url' => 'dashicons-share'
    ]);
  }

  /**
   * Adds the settings field group
   *
   * @return void
   */
  private function add_field_group() {
    $fields = ['text', 'textarea', 'image'];
    $field_types = [];
    foreach( $fields as $key ) {
      $field_type = $this->is_qtranslate_enabled() ? "qtranslate_$key" : $key;
      $field_types[$key] = array_merge( acf_get_field_type($field_type)->defaults, ['type' => $field_type]);
    }

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
        [
          'key' => "key_{$this->prefix}_noindex",
          'name' => "{$this->prefix}_noindex",
          'label' => __('Hide from search engines', 'rhseo'),
          'type' => 'true_false',
          'ui' => true,
        ],
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

  /**
   * Detects the options page
   *
   * @return bool
   */
  private function is_options_page(): bool {
    global $pagenow;
    if( $pagenow !== 'admin.php' ) return false;
    if( ($_GET['page'] ?? false) !== "{$this->prefix}-options") return false;
    return true;
  }
}

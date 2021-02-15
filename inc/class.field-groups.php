<?php 

namespace R\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * SEO Field_Groups
 */
class Field_Groups {
  
  private $prefix;
  private $field_group_locations;

  public function __construct() {
    $this->prefix = seo()->prefix;
    add_action('init', [$this, 'init'], 11);
  }

  /**
   * Add SEO Options Page
   *
   * @return void
   */
  public function init() {
    $this->setup_field_group_locations();
    $this->add_options_pages();
    $this->add_fields();
  }

  /**
   * Set up the locations for the field group
   */
  private function setup_field_group_locations(): void {
    $this->field_group_locations = [
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
      ]
    ];
  }
  
  /**
   * Adds options pages
   *
   * @return void
   */
  public function add_options_pages() {
    // add global options
    acf_add_options_page([
      'page_title' => __('SEO Options', 'rhseo'),
      'menu_slug' => "rhseo-options",
      'post_id' => "rhseo-options", 
      'position' => '59.6',
      'icon_url' => 'dashicons-share'
    ]);

    // add post type archive options pages
    $post_types = get_post_types([
      'public' => true
    ]);
    foreach( $post_types as $post_type ) {
      $pt_object = get_post_type_object($post_type); 
      if( !$pt_object->has_archive ) continue;
      $post_id = "rhseo-options--$post_type";
      acf_add_options_page([
        'page_title' => __('SEO Options', 'rhseo'),
        'menu_slug' => $post_id,
        'post_id' => $post_id, 
        'parent_slug' => "edit.php?post_type=$post_type",
      ]);
      $this->field_group_locations[] = [
        [
          'param' => 'options_page',
          'operator' => '==',
          'value' => $post_id,
        ],
      ];
    }
  }

  /**
   * Adds the settings field group
   *
   * @return void
   */
  private function add_fields() {
    $fields = ['text', 'textarea', 'image'];
    $field_types = [];
    foreach( $fields as $key ) {
      $field_type = $this->is_qtranslate_enabled() ? "qtranslate_$key" : $key;
      $field_types[$key] = $field_type;//array_merge( acf_get_field_type($field_type)->defaults, ['type' => $field_type]);
    }

    $fields = [
      [ 
        'type' => $field_types['textarea'],
        'key' => "key_{$this->prefix}_description",
        'name' => "{$this->prefix}_description",
        'label' => $this->is_global_options_page() ? __('Site Description', 'rhseo') : __('Description', 'rhseo'),
        'required' => false,
        'instructions' => __('Optimal length: 50–160 characters', 'rhseo'),
        'max' => 200,
        'rows' => 2,
        'acfml_multilingual' => true,
      ],
      [ 
        'type' => $field_types['image'],
        'key' => "key_{$this->prefix}_image",
        'preview_size' => 'medium',
        'return_format' => 'id',
        'name' => "{$this->prefix}_image",
        'label' => __('Preview Image', 'rhseo'),
        'required' => false,
        'instructions' => __('For services like Facebook or Twitter.', 'rhseo'),
        'acfml_multilingual' => true,
      ]
    ];

    if( $this->is_global_options_page() ) {
      $fields = array_merge([
        [ 
          'type' => $field_types['text'],
          'key' => "key_{$this->prefix}_site_name",
          'name' => "{$this->prefix}_site_name",
          'label' => __('Site Name', 'rhseo'),
          'acfml_multilingual' => true
        ],
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
        [ 
          'type' => $field_types['text'],
          'key' => "key_{$this->prefix}_document_title",
          'name' => "{$this->prefix}_document_title",
          'instructions' => __('Optional. Overwrites the title.', 'rhseo'),
          'label' => __('Document Title', 'rhseo'),
          'acfml_multilingual' => true
        ],
      ], $fields);
    }

    acf_add_local_field_group([
      'key' => "group_{$this->prefix}_options",
      'title' => $this->get_field_group_title(),
      'fields' => $fields,
      'location' => $this->field_group_locations,
      'menu_order' => 1000,
      'position' => 'normal',
      'active' => true,
    ]);
  }

  /**
   * Get the field group title in different contexts
   *
   * @return string
   */
  private function get_field_group_title(): string {
    $title = __('SEO', 'rhseo');
    if( $this->is_global_options_page() ) $title = __('SEO Global Defaults', 'rhseo');
    if( $post_type = $this->is_post_type_options_page() ) {
      $pt_object = get_post_type_object( $post_type );
      $title = sprintf(__('SEO Options: %s', 'rhseo'), $pt_object->labels->name);
    }
    return $title;
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
   * Detects the options page
   *
   * @return bool
   */
  private function is_global_options_page(): bool {
    global $pagenow;
    if( $pagenow !== 'admin.php' ) return false;
    if( ($_GET['page'] ?? false) !== "{$this->prefix}-options") return false;
    return true;
  }

  /**
   * Check if on an options page for a post type
   *
   * @return string|null
   */
  private function is_post_type_options_page(): ?string {
    global $pagenow;
    $post_type = null;
    if( $pagenow !== 'edit.php' ) return $post_type;
    if( !$page = $_GET['page'] ?? null ) return $post_type;
    foreach( $this->field_group_locations as $location ) {
      $location_value = $location[0]['value'] ?? null;
      if( $page === $location_value ) {
        $post_type = str_replace("rhseo-options--", "", $page);
      }
    }
    return $post_type;
  }
}

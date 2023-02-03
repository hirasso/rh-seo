<?php

namespace RAH\SEO;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * SEO Field_Groups
 */
class FieldGroups {

  private $prefix;
  private $field_group_locations;

  public function __construct() {
    $this->prefix = rhseo()->prefix;
    add_action('admin_bar_menu', [$this, 'add_post_type_archive_link_to_admin_bar'], 100);
    add_filter('acf/prepare_field/name=rhseo_noindex', [$this, 'prepare_field_noindex']);
    add_action('init', [$this, 'init'], 11);
    add_filter('acf/prepare_field', [$this, 'maybe_render_field']);
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
          'value' => "rhseo-options",
        ],
      ],
      [
        [
          'param' => 'taxonomy',
          'operator' => '==',
          'value' => "all",
        ],
      ],
    ];

    if( $pll_languages = rhseo()->get_polylang_languages() ) {
      foreach( $pll_languages as $language ) {
        $this->field_group_locations[] = [
          [
            'param' => 'options_page',
            'operator' => '==',
            'value' => "rhseo-options--$language->slug"
          ]
        ];
      }
    }

    $post_types = get_post_types(['public' => true]);

    foreach( $post_types as $post_type ) {
      $this->field_group_locations[] = [
        [
          'param' => 'post_type',
          'operator' => '==',
          'value' => $post_type,
        ],
        [
          'param' => 'page_type',
          'operator' => '!=',
          'value' => 'front_page',
        ]
      ];
    }

  }

  /**
   * Adds options pages
   *
   * @return void
   */
  public function add_options_pages() {
    // add global options
    $parent = acf_add_options_page([
      'page_title' => __('Search Engine Optimization', 'rhseo'),
      'menu_title' => __('SEO', 'rhseo'),
      'menu_slug' => "rhseo-options",
      'post_id' => "rhseo-options",
      'position' => '59.6',
      'icon_url' => 'dashicons-share',
    ]);


    if( $pll_languages = rhseo()->get_polylang_languages() ) {
      foreach( $pll_languages as $language ) {
        acf_add_options_sub_page([
          'page_title' => $language->name,
          'menu_title' => $language->name,
          'parent_slug' => $parent['menu_slug'],
          'menu_slug' => $parent['menu_slug'] . "--$language->slug",
          'post_id' => "rhseo-options--$language->slug"
        ]);
      }
    }

    // add post type archive options pages
    $post_types = \get_post_types([
      'public' => true
    ]);

    foreach( $post_types as $post_type ) {
      $pt_object = \get_post_type_object($post_type);
      if( !$pt_object->has_archive ) continue;
      $this->add_post_type_options_page($post_type);
    }
  }

  /**
   * Adds SEO options pages to post types
   *
   * @param string $post_type
   * @return void
   */
  private function add_post_type_options_page($post_type) {

    $pt_object = get_post_type_object($post_type);

    if( $pll_languages = rhseo()->get_polylang_languages() ) {
      foreach( $pll_languages as $language ) {
        $post_id = "rhseo-options--$language->slug--$post_type";
        acf_add_options_page([
          'page_title' => "SEO $language->name" . ": {$pt_object->labels->name}",
          'menu_title' => "SEO $language->name",
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
    } else {
      $post_id = "rhseo-options--$post_type";
      acf_add_options_page([
        'page_title' => __('SEO Options', 'rhseo') . ": {$pt_object->labels->name}",
        'menu_title' => __('SEO Options', 'rhseo'),
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
      $field_types[$key] = $field_type;
    }

    $fields = [
      [
        'key' => 'field_rhseo_tab_general',
        'type' => 'tab',
        'label' => 'General',
        'rhseo_render_field' => $this->is_global_options_page(),
      ],
      [
        'type' => $field_types['text'],
        'key' => "field_{$this->prefix}_site_name",
        'name' => "{$this->prefix}_site_name",
        'label' => __('Site Name', 'rhseo'),
        'acfml_multilingual' => true,
        'rhseo_render_field' => $this->is_global_options_page()
      ],
      [
        'key' => "field_{$this->prefix}_noindex",
        'name' => "rhseo_noindex",
        'label' => __('Hide from search engines', 'rhseo'),
        'type' => 'true_false',
        'ui' => true,
        'rhseo_render_field' => !$this->is_global_options_page()
      ],
      [
        'type' => $field_types['text'],
        'key' => "field_{$this->prefix}_document_title",
        'name' => "{$this->prefix}_document_title",
        'instructions' => __('Optional. Overwrites the title.', 'rhseo'),
        'label' => __('Document Title', 'rhseo'),
        'acfml_multilingual' => true,
        'rhseo_render_field' => !$this->is_global_options_page()
      ],
      [
        'type' => $field_types['textarea'],
        'key' => "field_{$this->prefix}_description",
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
        'key' => "field_{$this->prefix}_image",
        'preview_size' => 'medium',
        'return_format' => 'id',
        'name' => "{$this->prefix}_image",
        'label' => __('Preview Image', 'rhseo'),
        'required' => false,
        'instructions' => __('For services like Facebook or Twitter.', 'rhseo'),
        'acfml_multilingual' => true,
      ],
      [
        'key' => 'field_rhseo_tab_redirects',
        'type' => 'tab',
        'label' => '404 Redirects',
        'rhseo_render_field' => $this->is_global_options_page(),
      ],
      [
        'key' => 'field_rhseo_404_redirects',
        'type' => 'repeater',
        'max' => 100,
        'instructions' => __('Maximum 100 entries', 'rhseo'),
        'name' => 'rhseo_404_redirects',
        'label' => '404 Redirects',
        'button_label' => 'Add Redirect',
        'rhseo_render_field' => $this->is_global_options_page(),
        'layout' => 'block',
        'sub_fields' => [
          [
            'key' => 'field_rhseo_redirect_from',
            'name' => 'rhseo_redirect_from',
            'label' => __('From', 'rhseo'),
            'instructions' => __('URL that should be redirected', 'rhseo'),
            'type' => 'text',
            'required' => 1,
            'wrapper' => [
              'width' => 50
            ]
          ],
          [
            'key' => 'field_rhseo_redirect_to',
            'name' => 'rhseo_redirect_to',
            'label' => __('To', 'rhseo'),
            'instructions' => __('Redirect target URL', 'rhseo'),
            'type' => 'text',
            'required' => 1,
            'wrapper' => [
              'width' => 50
            ]
          ]
        ]
      ],
    ];

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
   * Maybe renders a field, based on the key `rhseo_render_field`
   *
   * @param [type] $field
   * @return void
   */
  public function maybe_render_field($field) {
    $render_field = $field['rhseo_render_field'] ?? true;
    return $render_field ? $field : null;
  }

  /**
   * Get the field group title in different contexts
   *
   * @return string
   */
  private function get_field_group_title(): string {
    $title = __('SEO', 'rhseo');
    if( $this->is_global_options_page() ) $title = __('SEO Global Options', 'rhseo');
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
  private function is_qtranslate_enabled(): bool {
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
    if( !$page = $_GET['page'] ?? false ) return false;
    return strpos($page, "rhseo-options") === 0;
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
        $parts = explode('--', $page);
        $post_type = end($parts);
      }
    }
    return $post_type;
  }

  /**
   * Hide field 'noindex' on post type options pages
   *
   * @param array $field
   * @return mixed
   */
  public function prepare_field_noindex($field) {
    if( $this->is_post_type_options_page() ) return false;
    return $field;
  }

  /**
   * Add and item to the admin bar on Post Type Options Pages
   *
   * @param \WP_Admin_Bar $wp_adminbar
   * @return void
   */
  public function add_post_type_archive_link_to_admin_bar( $wp_adminbar ) {
    if( !$post_type = $this->is_post_type_options_page() ) return;
    if( !is_post_type_viewable($post_type) ) return;
    if( !$link = get_post_type_archive_link($post_type) ) return;

    $pt_object = get_post_type_object( $post_type );
    // add Forum sub-menu item
    $wp_adminbar->add_node([
      'id' => 'view-post-type',
      'title' => $pt_object->labels->view_items,
      'href' => $link,
      'meta' => [
        'target' => '_blank'
      ]
    ]);
  }
}

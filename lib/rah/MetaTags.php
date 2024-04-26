<?php

namespace RAH\SEO;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * SEO Meta Tags
 */
class MetaTags
{

    public function __construct()
    {
        add_action('wp_head', [$this, 'wp_head'], 4);
        add_filter('pre_option_blogname', [$this, 'filter_option_blogname']);
        add_filter('pre_option_blogdescription', [$this, 'filter_option_blogdescription']);
        add_filter('document_title_parts', [$this, 'document_title_parts']);
        add_action('wp', [$this, 'adjust_robots']);
    }

    /**
     * Adjust wp_robots to add noindex for selected objects
     *
     * @return void
     */
    public function adjust_robots(): void
    {
        if (is_admin()) return;
        $is_noindex = rhseo()->object_is_set_to_noindex(rhseo()->get_queried_object());
        if ($is_noindex) add_filter('wp_robots', 'wp_robots_no_robots');
    }

    /**
     * Inject SEO meta tags
     *
     * @return void
     */
    public function wp_head(): void
    {
        // allow to disable meta tags
        if (!apply_filters('rhseo/render_meta_tags', true)) return;

        ob_start() ?>

        <!-- SEO: Start -->
        <meta property="og:title" content="<?= wp_get_document_title() ?>">
        <?php if ($description = get_bloginfo('description')) : ?>
            <meta name="description" content="<?= esc_attr($description) ?>">
            <meta property="og:description" content="<?= esc_attr($description) ?>">
        <?php endif; ?>
        <meta property="og:locale" content="<?= esc_attr($this->get_locale()) ?>">
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?= esc_attr(rhseo()->get_current_url()) ?>">
        <meta property="og:site_name" content="<?= esc_attr(get_bloginfo('name')) ?>">
        <?php if ($image = $this->get_og_image_url()) : ?>
            <meta property="og:image" content="<?= esc_attr($image) ?>">
        <?php endif; ?>
        <meta name="twitter:card" content="summary_large_image">
        <!-- SEO: End -->

<?php echo rhseo()->trim_html(ob_get_clean());
    }


    /**
     * Get locale for tag
     *
     * @return string Locale
     */
    private function get_locale(): string
    {
        $lang = get_bloginfo('language');
        return str_replace('-', '_', $lang);
    }

    /**
     * Filter document title parts
     */
    public function document_title_parts(array $parts): array
    {

        $parts['title'] = $this->get_seo_value('document_title');

        // We never want the tagline in the document title
        unset($parts['tagline']);

        // On the front page we want only the site name
        if (rhseo()->is_front_page()) {
            unset($parts['title']);
        }

        // We always want to use our custom site name in the document title
        $parts['site'] = get_bloginfo('name');

        return $parts;
    }

    /**
     * Filters get_bloginfo('name')
     */
    public function filter_option_blogname(string $value): string
    {

        if ($custom_site_name = rhseo()->get_global_options_field('site_name')) {
            return __($custom_site_name);
        }

        return $value;
    }

    /**
     * Filters get_bloginfo('description')
     *
     * @param string $name
     * @return string|bool
     */
    public function filter_option_blogdescription($value)
    {
        if ($custom_value = $this->get_seo_value('description')) {
            return $this->prepare_meta_text_field($custom_value);
        }
        return $value;
    }

    /**
     * Prepare a text field for display in a meta tag
     *
     * @param string $value
     * @return string
     */
    private function prepare_meta_text_field(string $value): string
    {
        $value = __($value);
        $value = str_replace('&nbsp;', ' ', $value);
        return $value;
    }

    /**
     * Get an SEO value, fall back to the global default
     *
     * Warning:
     * Do not use get_option() or get_bloginfo() here, to prevent infinite loops.
     *
     * @param string $name
     * @return string|null
     */
    public function get_seo_value(string $name, $object = null): ?string
    {

        if (!$object) $object = rhseo()->get_queried_object();

        if ($object) $value = rhseo()->get_field($name, $this->get_acf_post_id($object));

        // fallbacks for document_title
        if (empty($value) && $name === 'document_title') {
            if ($object instanceof \WP_Post) $value = get_the_title($object->ID);
            if ($object instanceof \WP_Post_Type) $value = $object->labels->name;
            if ($object instanceof \WP_Term) $value = $object->name;
        }
        // fallbacks for 'image'
        if (empty($value) && $name === 'image') {
            if ($object instanceof \WP_Post) $value = get_post_thumbnail_id($object->ID);
        }
        // finally fall back to global options
        if (empty($value)) $value = rhseo()->get_global_options_field($name);
        /**
         * Allow themes to filter SEO values
         */
        $value = apply_filters("rhseo/get_seo_value/name=$name", $value, $object);

        return $value ?? null;
    }

    /**
     * Get the acf post id for different WP Objects
     *
     * @param null|false|WP_Post|WP_Term|WP_Post_Type $object
     * @return mixed
     */
    private function get_acf_post_id($object)
    {
        $post_id = null;
        if ($object instanceof \WP_Post) {
            $post_id = $object;
        } elseif ($object instanceof \WP_Term) {
            $post_id = $object;
        } elseif ($object instanceof \WP_Post_Type) {
            $post_id = rhseo()->get_options_page_slug() . "--$object->name";
        }
        return $post_id;
    }

    /**
     * Get OG Image
     *
     * @return mixed
     */
    private function get_og_image_url()
    {
        /**
         * Allow to short-circuit this request
         */
        if (!$value = apply_filters("rhseo/og_image_id", null)) {
            $value = $this->get_seo_value('image');
        }

        // bail early if empty
        if (empty($value)) return $value;

        // Handle objects or scalars
        $value = $value['ID'] ?? $value;

        // get the URL
        $value = is_numeric($value) ? wp_get_attachment_url($value) : $value;

        return $value;
    }
}

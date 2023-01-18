<?php

namespace R\SEO;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * SEO Field_Groups
 */
class Redirects
{

  public function __construct()
  {
    add_filter('acf/update_value/name=rhseo_redirect_from', [$this, 'update_value_url']);
    add_filter('acf/update_value/name=rhseo_redirect_to', [$this, 'update_value_url']);
    add_filter('acf/validate_value/name=rhseo_redirect_from', [$this, 'validate_value_url'], 10, 4);
    add_filter('acf/validate_value/name=rhseo_redirect_to', [$this, 'validate_value_url'], 10, 4);
    add_action('template_redirect', [$this, 'template_redirect']);
  }

  /**
   * Makes sure a value is either an absolute or relative URL
   *
   * @param [type] $valid
   * @param [type] $value
   * @param [type] $field
   * @param [type] $input_name
   * @return void
   */
  public function validate_value_url($valid, $value, $field, $input_name)
  {
    if ($valid !== true) return $valid;
    if (!is_string($value)) return $valid;

    if (str_starts_with($value, 'http')) return $valid;
    if (str_starts_with($value, '/')) return $valid;

    return __('Please provide either an absolute or relative URL', 'rhseo');
  }

  /**
   * Convert absolute URLs to relative URLs
   *
   * @param [type] $value
   * @return void
   */
  public function update_value_url($value)
  {
    if (empty($value)) return $value;
    return $this->get_request_uri($value);
  }

  /**
   * Get the request URI from an URL string
   *
   * @param string $url
   * @return void
   */
  private function get_request_uri(string $url)
  {
    $parsed = wp_parse_url($url);
    $uri = $parsed['path'] ?? null ?: '/';
    $query = $parsed['query'] ?? null;
    if ($query) $uri .= "?$query";
    return strtolower($uri);
  }

  /**
   * Redirect 404 pages
   */
  public function template_redirect(): void
  {
    if (!is_404()) return;

    $request_uri = $this->get_request_uri(seo()->get_current_url());

    $redirects = get_field('rhseo_404_redirects', 'rhseo-options') ?: [];

    $redirects = array_map(function ($redirect) {
      return (object) [
        'from' => $this->get_request_uri($redirect['rhseo_redirect_from']),
        'to' => $this->get_request_uri($redirect['rhseo_redirect_to']),
        'target_url' => $redirect['rhseo_redirect_to']
      ];
    }, $redirects);


    foreach ($redirects as $redirect) {
      // ignore non-matches
      if ($redirect->from !== $request_uri) continue;
      // prevent redirect loops
      if ($redirect->to === $request_uri) continue;
      // Finally, redirect
      wp_redirect($redirect->target_url, 301);
      exit;
    }
  }
}

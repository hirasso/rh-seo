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

    $value = $this->get_request_uri($value);

    if (!$this->is_absolute_or_relative_url($value)) {
      return __('Please provide either an absolute or relative URL', 'rhseo');
    }
    $redirects = $this->get_redirects();

    if ($field['_name'] === 'rhseo_redirect_to' && in_array($value, array_column($redirects, 'from'))) {
      return __('Conflict detected: URL exists in "From" column', 'rhseo');
    }

    if ($field['_name'] === 'rhseo_redirect_from' && in_array($value, array_column($redirects, 'to'))) {
      return __('Conflict detected: URL exists in "To" column', 'rhseo');
    }

    return $valid;
  }

  private function is_absolute_or_relative_url(string $value): bool {
    if (str_starts_with($value, 'http')) return true;
    if (str_starts_with($value, '/')) return true;
    return false;
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
   * @return string
   */
  private function get_request_uri(string $url): string
  {
    $parsed = wp_parse_url($url);
    $uri = $parsed['path'] ?? null ?: '/';
    $query = $parsed['query'] ?? null;
    if ($query) {
      $uri .= "?$query";
    } else {
      $uri = trailingslashit($uri);
    }
    return strtolower($uri);
  }

  /**
   * Get and sanitize the redirects
   *
   * @return array
   */
  private function get_redirects(): array
  {
    $redirects = get_field('rhseo_404_redirects', 'rhseo-options') ?: [];

    $redirects = array_map(function ($redirect) {
      return [
        'from' => $this->get_request_uri($redirect['rhseo_redirect_from']),
        'to' => $this->get_request_uri($redirect['rhseo_redirect_to']),
      ];
    }, $redirects);

    return $redirects;
  }

  /**
   * Redirect 404 pages
   */
  public function template_redirect(): void
  {
    if (!is_404()) return;

    $request_uri = $this->get_request_uri(seo()->get_current_url());


    foreach ($this->get_redirects() as $redirect) {
      // ignore non-matches
      if ($redirect['from'] !== $request_uri) continue;
      // prevent redirect loops
      if ($redirect['to'] === $request_uri) continue;
      // Finally, redirect
      wp_redirect($redirect['to'], 301);
      exit;
    }
  }
}

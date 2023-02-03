<?php

namespace RAH\SEO;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * SEO Field_Groups
 */
class Redirects
{

  private $redirects = null;

  public function __construct()
  {
    add_filter('acf/update_value/name=rhseo_redirect_from', [$this, 'update_value_url']);
    add_filter('acf/update_value/name=rhseo_redirect_to', [$this, 'update_value_url']);

    add_filter('acf/validate_value/name=rhseo_redirect_from', [$this, 'validate_value_url'], 10, 4);
    add_filter('acf/validate_value/name=rhseo_redirect_to', [$this, 'validate_value_url'], 10, 4);

    add_filter('acf/prepare_field/name=rhseo_redirect_from', [$this, 'prepare_field_url']);
    add_filter('acf/prepare_field/name=rhseo_redirect_to', [$this, 'prepare_field_url']);

    add_action('template_redirect', [$this, 'template_redirect'], 1);
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

    if (!$this->is_local_url($value)) {
      return __('Please provide a local URL', 'rhseo');
    }

    $value = $this->get_request_uri($value);

    return $valid;
  }

  /**
   * Checks if a value is an absolute or relative URL
   *
   * @param string $value
   * @return boolean
   */
  private function is_local_url(string $value): bool {
    if (empty($value)) return false;
    if ($this->get_url_host($value) === $this->get_url_host(get_option('home'))) return true;
    if (str_starts_with($value, '/')) return true;
    return false;
  }

  /**
   * Get the host for a URL
   *
   * @param string $url
   * @return string|null
   */
  private function get_url_host(string $url): ?string {
    $parsed = wp_parse_url($url);
    return $parsed['host'] ?? null;
  }

  /**
   * Render conflict warnings for URL fields
   *
   * @param [type] $field
   * @return void
   */
  public function prepare_field_url($field) {
    if (empty($field)) return $field;
    if (empty($field['value'])) return $field;

    $redirects = $this->get_redirects();

    if ($field['_name'] === 'rhseo_redirect_from' && in_array($field['value'], array_column($redirects, 'to'))) {
      $field['instructions'] = "⚠️ " . __('Conflict detected: URL also found in "To" column', 'rhseo');
    }

    if ($field['_name'] === 'rhseo_redirect_to' && in_array($field['value'], array_column($redirects, 'from'))) {
      $field['instructions'] = "⚠️ " . __('Conflict detected: URL also found in "From" column', 'rhseo');
    }

    return $field;
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
    }
    if ($uri === "/") return $uri;
    return untrailingslashit(strtolower($uri));
  }

  /**
   * Get and sanitize the redirects
   *
   * @return array
   */
  private function get_redirects(): array
  {
    if ($this->redirects) return $this->redirects;
    $redirects = get_field('rhseo_404_redirects', 'rhseo-options') ?: [];

    $this->redirects = array_map(function ($redirect) {
      return [
        'from' => $this->get_request_uri($redirect['rhseo_redirect_from']),
        'to' => $this->get_request_uri($redirect['rhseo_redirect_to']),
      ];
    }, $redirects);

    return $this->redirects;
  }

  /**
   * Redirect 404 pages
   */
  public function template_redirect(): void
  {
    if (!is_404()) return;

    $request_uri = $this->get_request_uri(rhseo()->get_current_url());


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

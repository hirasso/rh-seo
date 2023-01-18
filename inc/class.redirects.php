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
    $this->prefix = seo()->prefix;
    add_action('template_redirect', [$this, 'template_redirect']);
  }

  /**
   * Get the request URI from an URL string
   *
   * @param string $url
   * @return void
   */
  private function get_request_uri(string $url) {
    $parsed = wp_parse_url($url);
    $uri = $parsed['path'] ?? null ?: '/';
    $query = $parsed['query'] ?? null;
    if ($query) $uri .= "?$query";
    return $uri;
  }

  /**
   * Redirect 404 pages
   */
  public function template_redirect(): void
  {
    if (!is_404()) return;

    $request_uri = $this->get_request_uri(seo()->get_current_url());

    $redirects = get_field('rhseo_404_redirects', 'rhseo-options') ?: [];

    $redirects = array_map(function($redirect) {
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

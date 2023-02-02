<?php

namespace R\SEO;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Migrate_ACF_Field_Keys
{

  private array $migrated = [
    'postmeta' => 0,
    'termmeta' => 0,
    'options' => 0
  ];

  public function __construct(){
    add_action('rhseo/do_database_upgrade', [$this, 'do_migration']);
  }

  public function do_migration()
  {
    global $wpdb;
    $this->migrated['postmeta'] = $this->migrate_meta_entries($wpdb->postmeta);
    $this->migrated['termmeta'] = $this->migrate_meta_entries($wpdb->termmeta);
    $this->migrated['options'] = $this->migrate_options_entries($wpdb->options);
  }

  /**
   * Migrate meta entries to use "field_" instead of "key_"
   *
   * @param string $table
   * @return int
   */
  private function migrate_meta_entries(string $table): int
  {
    global $wpdb;

    /**
     * Get all postmeta entries with a meta value starting with "key_rhseo_"
     */
    $entries = $wpdb->get_results(
      "SELECT *
      FROM $table
      WHERE meta_value LIKE 'key_rhseo_%'"
    );

    foreach ($entries as $entry) {
      $meta_id = (int) $entry->meta_id;
      $meta_value = $entry->meta_value;

      /**
       * Check again if the meta value really starts with "key_rhseo_"
       */
      if (!str_starts_with($meta_value, 'key_rhseo_')) continue;

      $new_meta_value = str_replace('key_rhseo_', 'field_rhseo_', $meta_value);

      /**
       * Update the meta value with the recommended "field_" prefix
       */
      $wpdb->query(
        "UPDATE $table SET meta_value = '$new_meta_value'
        WHERE meta_id = $meta_id"
      );
    }

    return count($entries);
  }

  /**
   * Migrate all options to use "field_" instead of "key_"
   *
   * @param string $table
   * @return int
   */
  private function migrate_options_entries(string $table): int
  {
    global $wpdb;
    /**
     * Get all postmeta entries with a meta value starting with "key_rhseo_"
     */
    $entries = $wpdb->get_results(
      "SELECT *
      FROM $table
      WHERE option_value LIKE 'key_rhseo_%'"
    );

    foreach ($entries as $entry) {
      $id = (int) $entry->option_id;
      $value = $entry->option_value;

      /**
       * Check again if the meta value really starts with "key_rhseo_"
       */
      if (!str_starts_with($value, 'key_rhseo_')) continue;

      $new_value = str_replace('key_rhseo_', 'field_rhseo_', $value);

      /**
       * Update the meta value with the recommended "field_" prefix
       */
      $wpdb->query(
        "UPDATE $table SET option_value = '$new_value'
        WHERE option_id = $id"
      );
    }
    return count($entries);
  }
}

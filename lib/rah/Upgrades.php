<?php

namespace RAH\SEO;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Manages Database Upgrades
 */
class Upgrades
{

  public function __construct()
  {
    // for dev: reset the db version on each load
    // $this->update_db_version('0.0.1');

    add_action('admin_notices', [$this, 'maybe_show_upgrade_notice']);
    add_action('admin_notices', [$this, 'maybe_show_upgrade_success_notice']);
    add_action('admin_init', [$this, 'maybe_upgrade_database']);
  }

  /**
   * Checks if an upgrade is needed
   *
   * @return boolean
   */
  public function needs_upgrade(): bool
  {
    $db_version = $this->get_db_version();

    if ($db_version && version_compare($db_version, RHSEO_UPGRADE_VERSION, '<')) {
      return true;
    }

    return false;
  }

  /**
   * Check if the current user can perform upgrades
   *
   * @return boolean
   */
  private function user_can_perform_upgrades(): bool
  {
    return current_user_can('edit_others_posts');
  }

  /**
   * Get the db version for upgrades
   *
   * @return string
   */
  public function get_db_version(): string
  {
    return get_option('rhseo_version', '0.0.1');
  }

  /**
   * Update the version in the DB
   *
   * @param string $version
   * @return void
   */
  public function update_db_version($version = ''): void
  {
    update_option('rhseo_version', $version);
  }

  /**
   * Show a notice to start an upgrade if needed
   *
   * @return void
   */
  public function maybe_show_upgrade_notice(): void
  {
    // Bail early if no upgrade is required
    if (!$this->needs_upgrade()) return;

    // Bail early if the current user isn't allowed to perform upgrades
    if (!$this->user_can_perform_upgrades()) return;

    $url = wp_nonce_url(add_query_arg(['action' => 'rhseo-upgrade-database']), 'rhseo-upgrade-database');
    ob_start() ?>
    <div class="notice notice-info">
      <p><?php _e('RH SEO needs to upgrade the database.', 'rhseo'); ?> <a class="button-primary" href="<?= $url ?>"><?= __('Upgrade Database Now', 'rhseo') ?></a></p>
    </div>
  <?php echo ob_get_clean();
  }

  /**
   * Performs a database upgrade if certain conditions apply
   *
   * @return void
   */
  public function maybe_upgrade_database(): void
  {
    // Bail early if no upgrade is required
    if (!$this->needs_upgrade()) return;

    // Bail early if the current user isn't allowed to perform upgrades
    if (!$this->user_can_perform_upgrades()) return;

    // Bail early if the required query param "action" is missing
    $action = $_GET['action'] ?? null;
    if ($action !== 'rhseo-upgrade-database') return;

    // Bail early if the nonce check fails
    $nonce_check = (bool) wp_verify_nonce($_GET['_wpnonce'] ?? null, 'rhseo-upgrade-database');
    if (!$nonce_check) wp_die(__('Something went wrong.', 'rhseo'), __('Error', 'rhseo'), ['back_link' => true]);

    $this->perform_upgrade();

    $new_url = remove_query_arg('action');
    $new_url = remove_query_arg('_wpnonce', $new_url);

    update_option('rhseo_show_upgrade_success_notice', get_current_user_id());

    wp_redirect($new_url);
    exit;
  }

  /**
   * Performs the actual upgrade
   *
   *  - Fires an action "rhseo/do_database_upgrade"
   *  - Updates the version in the database
   *
   * @return void
   */
  private function perform_upgrade(): void
  {
    // Increase time limit if possible.
    if ( function_exists( 'set_time_limit' ) ) {
      set_time_limit( 600 );
    }

    do_action('rhseo/do_database_upgrade');
    $this->update_db_version(RHSEO_UPGRADE_VERSION);
  }

  /**
   * Shows an admin notice after a successful upgrade
   *
   * @return void
   */
  public function maybe_show_upgrade_success_notice(): void
  {
    if ((int) get_option('rhseo_show_upgrade_success_notice') !== get_current_user_id()) return;

    delete_option('rhseo_show_upgrade_success_notice');

    ob_start() ?>
    <div class="notice notice-info is-dismissible">
      <p><?php _e('RH SEO successfully upgraded the database.', 'rhseo'); ?></p>
    </div>
<?php echo ob_get_clean();
  }

}

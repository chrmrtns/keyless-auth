<?php
/**
 * Admin coordinator for Keyless Auth
 *
 * This class coordinates all admin functionality by initializing
 * the various admin components (menus, pages, settings, assets, ajax).
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use Chrmrtns\KeylessAuth\Admin\MenuManager;
use Chrmrtns\KeylessAuth\Admin\Settings\SettingsManager;
use Chrmrtns\KeylessAuth\Admin\Assets\AssetLoader;
use Chrmrtns\KeylessAuth\Admin\Ajax\TwoFAAjaxHandler;

class Admin {

    /**
     * Menu manager instance
     */
    private $menu_manager;

    /**
     * Settings manager instance
     */
    private $settings_manager;

    /**
     * Asset loader instance
     */
    private $asset_loader;

    /**
     * AJAX handler instance
     */
    private $ajax_handler;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize components
        $this->menu_manager = new MenuManager();
        $this->settings_manager = new SettingsManager();
        $this->asset_loader = new AssetLoader();
        $this->ajax_handler = new TwoFAAjaxHandler();

        // Register hooks
        add_action('admin_init', array($this, 'handle_notification_dismiss'));
        add_action('admin_notices', array($this, 'display_admin_notice'));
    }

    /**
     * Handle notification dismiss
     */
    public function handle_notification_dismiss() {
        if (isset($_GET['chrmrtns_kla_learn_more_dismiss_notification']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'chrmrtns_kla_learn_more_dismiss_notification')) {
                update_option('chrmrtns_kla_learn_more_dismiss_notification', true);
                wp_safe_redirect(remove_query_arg(array('chrmrtns_kla_learn_more_dismiss_notification', '_wpnonce')));
                exit;
            }
        }
    }

    /**
     * Display admin notices
     */
    public function display_admin_notice() {
        // Check if emergency mode is enabled
        $emergency_mode_enabled = false;
        if (defined('CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY') && CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY === true) {
            $emergency_mode_enabled = true;
        }
        if (get_option('chrmrtns_kla_2fa_emergency_disable', false)) {
            $emergency_mode_enabled = true;
        }

        if ($emergency_mode_enabled) {
            // Check if user has dismissed the notice temporarily
            $user_id = get_current_user_id();
            $dismissed = get_transient('chrmrtns_kla_emergency_notice_dismissed_' . $user_id);

            if (!$dismissed) {
                ?>
                <div class="notice notice-error is-dismissible chrmrtns-emergency-notice" data-notice-id="emergency-mode">
                    <p><strong><?php esc_html_e('🚨 Keyless Auth - Emergency Mode Active', 'keyless-auth'); ?></strong></p>
                    <p><?php esc_html_e('Two-Factor Authentication is currently disabled via emergency mode. This should only be used temporarily if you are locked out.', 'keyless-auth'); ?></p>
                    <p>
                        <button type="button" class="button button-secondary" id="chrmrtns-disable-emergency-mode">
                            <?php esc_html_e('Disable Emergency Mode', 'keyless-auth'); ?>
                        </button>
                    </p>
                </div>

                <script>
                jQuery(document).ready(function($) {
                    // Dismiss notice temporarily
                    $('.chrmrtns-emergency-notice').on('click', '.notice-dismiss', function() {
                        $.post('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                            action: 'chrmrtns_dismiss_emergency_notice',
                            nonce: '<?php echo esc_js(wp_create_nonce('chrmrtns_kla_ajax_nonce')); ?>'
                        });
                    });

                    // Disable emergency mode
                    $('#chrmrtns-disable-emergency-mode').on('click', function(e) {
                        e.preventDefault();
                        if (confirm('<?php echo esc_js(__('Are you sure you want to disable emergency mode? This will re-enable 2FA security.', 'keyless-auth')); ?>')) {
                            $.post('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                                action: 'chrmrtns_disable_emergency_mode',
                                nonce: '<?php echo esc_js(wp_create_nonce('chrmrtns_kla_ajax_nonce')); ?>'
                            }, function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert('<?php echo esc_js(__('Failed to disable emergency mode. Please check your settings.', 'keyless-auth')); ?>');
                                }
                            });
                        }
                    });
                });
                </script>
                <?php
            }
        }

        // Show 2FA system status if 2FA was recently enabled/disabled
        $tfa_system_active = get_transient('chrmrtns_kla_2fa_system_status_' . get_current_user_id());

        if ($tfa_system_active && !$emergency_mode_enabled) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php esc_html_e('✓ Two-Factor Authentication system is now active', 'keyless-auth'); ?></strong></p>
                <p><?php esc_html_e('Users in required roles will be prompted to set up 2FA on their next login. Grace period settings apply.', 'keyless-auth'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Get menu manager instance
     *
     * @return MenuManager
     */
    public function get_menu_manager() {
        return $this->menu_manager;
    }

    /**
     * Get settings manager instance
     *
     * @return SettingsManager
     */
    public function get_settings_manager() {
        return $this->settings_manager;
    }
}

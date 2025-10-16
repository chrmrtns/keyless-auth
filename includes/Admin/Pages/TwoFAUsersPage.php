<?php
/**
 * 2FA Users Management page for Keyless Auth admin
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Pages;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use Chrmrtns\KeylessAuth\Core\Database;

class TwoFAUsersPage {

    /**
     * Render the 2FA users management page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }

        global $chrmrtns_kla_database;

        if (!$chrmrtns_kla_database) {
            wp_die(esc_html__('Database not available.', 'keyless-auth'));
        }

        // Handle search
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for search functionality, no form processing
        $search_query = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $users_with_2fa = $chrmrtns_kla_database->get_2fa_users($search_query);

        ?>
        <div class="wrap">
            <h1 class="chrmrtns-header">
                <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
                <?php esc_html_e('2FA User Management', 'keyless-auth'); ?>
            </h1>

            <div class="chrmrtns_kla_card">
                <p class="description"><?php esc_html_e('Manage 2FA settings for individual users. You can search for specific users and disable 2FA if needed.', 'keyless-auth'); ?></p>

                <!-- Search Form -->
                <form method="get" style="margin: 20px 0;">
                    <input type="hidden" name="page" value="keyless-auth-2fa-users" />
                    <p class="search-box">
                        <label class="screen-reader-text" for="user-search-input"><?php esc_html_e('Search Users:', 'keyless-auth'); ?></label>
                        <input type="search" id="user-search-input" name="search" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Search by username or email...', 'keyless-auth'); ?>" />
                        <?php submit_button(__('Search Users', 'keyless-auth'), 'secondary', 'search_submit', false); ?>
                        <?php if (!empty($search_query)): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=keyless-auth-2fa-users')); ?>" class="button"><?php esc_html_e('Clear', 'keyless-auth'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>

                <?php if (empty($users_with_2fa)): ?>
                    <?php if (!empty($search_query)): ?>
                        <p><em><?php
                        /* translators: %s: search query term */
                        printf(esc_html__('No users found matching "%s".', 'keyless-auth'), esc_html($search_query)); ?></em></p>
                        <p><a href="<?php echo esc_url(admin_url('admin.php?page=keyless-auth-2fa-users')); ?>"><?php esc_html_e('Show all 2FA users', 'keyless-auth'); ?></a></p>
                    <?php else: ?>
                        <p><em><?php esc_html_e('No users with 2FA enabled yet. Users can set up 2FA using the [keyless-auth-2fa] shortcode.', 'keyless-auth'); ?></em></p>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (!empty($search_query)): ?>
                        <p><strong><?php
                        /* translators: 1: search query, 2: number of users found */
                        printf(esc_html__('Search results for "%1$s" (%2$d users found):', 'keyless-auth'), esc_html($search_query), count($users_with_2fa)); ?></strong></p>
                    <?php else: ?>
                        <p><strong><?php
                        /* translators: %d: total number of users with 2FA enabled */
                        printf(esc_html__('Total users with 2FA: %d', 'keyless-auth'), count($users_with_2fa)); ?></strong></p>
                    <?php endif; ?>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('User', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Email', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Role', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('2FA Status', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Last Used', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Failed Attempts', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Backup Codes', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'keyless-auth'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_with_2fa as $user): ?>
                                <?php $wp_user = get_user_by('ID', $user->ID); ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($user->user_login); ?></strong>
                                        <br><small><?php echo esc_html($user->display_name); ?></small>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td>
                                        <?php
                                        if ($wp_user) {
                                            $roles = $wp_user->roles;
                                            echo esc_html(ucfirst(implode(', ', $roles)));
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user->totp_enabled): ?>
                                            <span style="color: #46b450;">✓ <?php esc_html_e('Enabled', 'keyless-auth'); ?></span>
                                            <?php if ($user->totp_locked_until && strtotime($user->totp_locked_until) > time()): ?>
                                                <br><span style="color: #dc3232;">🔒 <?php esc_html_e('Locked', 'keyless-auth'); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #666;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($user->totp_last_used) {
                                            echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($user->totp_last_used)));
                                        } else {
                                            echo '<em>' . esc_html__('Never', 'keyless-auth') . '</em>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($user->totp_enabled) {
                                            $attempts = intval($user->totp_failed_attempts);
                                            if ($attempts > 0) {
                                                echo '<span style="color: #dc3232;">' . esc_html($attempts) . '</span>';
                                            } else {
                                                echo '0';
                                            }
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($user->totp_enabled) {
                                            // Get backup codes from the user settings
                                            $user_settings = $chrmrtns_kla_database->get_user_2fa_settings($user->ID);
                                            if ($user_settings && !empty($user_settings->totp_backup_codes)) {
                                                $backup_codes = $user_settings->totp_backup_codes;
                                                $total_codes = count($backup_codes);
                                                $remaining = $total_codes; // For now, assume all are available (we don't track usage individually)

                                                if ($remaining === 0) {
                                                    echo '<span style="color: #dc3232;">' . esc_html($remaining) . '/' . esc_html($total_codes) . '</span>';
                                                } elseif ($remaining < 3) {
                                                    echo '<span style="color: #ffb900;">' . esc_html($remaining) . '/' . esc_html($total_codes) . '</span>';
                                                } else {
                                                    echo '<span style="color: #46b450;">' . esc_html($remaining) . '/' . esc_html($total_codes) . '</span>';
                                                }
                                            } else {
                                                echo '<em>' . esc_html__('None', 'keyless-auth') . '</em>';
                                            }
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user->totp_enabled): ?>
                                            <button type="button" class="button button-secondary button-small" onclick="chrmrtnsDisable2FA(<?php echo intval($user->ID); ?>, '<?php echo esc_js($user->user_login); ?>')">
                                                <?php esc_html_e('Disable 2FA', 'keyless-auth'); ?>
                                            </button>
                                            <?php if ($user->totp_locked_until && strtotime($user->totp_locked_until) > time()): ?>
                                                <br><button type="button" class="button button-secondary button-small" style="margin-top: 5px;" onclick="chrmrtnsUnlock2FA(<?php echo intval($user->ID); ?>, '<?php echo esc_js($user->user_login); ?>')">
                                                    <?php esc_html_e('Unlock', 'keyless-auth'); ?>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <script>
        function chrmrtnsDisable2FA(userId, username) {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to disable 2FA for', 'keyless-auth')); ?> "' + username + '"?\n\n<?php echo esc_js(__('This action will:', 'keyless-auth')); ?>\n- <?php echo esc_js(__('Remove their TOTP secret', 'keyless-auth')); ?>\n- <?php echo esc_js(__('Delete all backup codes', 'keyless-auth')); ?>\n- <?php echo esc_js(__('Clear any lockouts', 'keyless-auth')); ?>')) {
                return;
            }

            var data = {
                'action': 'chrmrtns_kla_admin_disable_2fa',
                'user_id': userId,
                'nonce': '<?php echo esc_js(wp_create_nonce('chrmrtns_kla_ajax_nonce')); ?>'
            };

            jQuery.post(ajaxurl, data, function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }

        function chrmrtnsUnlock2FA(userId, username) {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to unlock 2FA for', 'keyless-auth')); ?> "' + username + '"?')) {
                return;
            }

            // You could add an unlock AJAX handler here if needed
            alert('<?php echo esc_js(__('Unlock functionality can be added if needed.', 'keyless-auth')); ?>');
        }
        </script>
        <?php
    }
}

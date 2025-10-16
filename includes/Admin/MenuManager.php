<?php
/**
 * Admin menu manager for Keyless Auth
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use Chrmrtns\KeylessAuth\Admin\Pages\DashboardPage;
use Chrmrtns\KeylessAuth\Admin\Pages\TemplatesPage;
use Chrmrtns\KeylessAuth\Admin\Pages\SmtpPage;
use Chrmrtns\KeylessAuth\Admin\Pages\MailLogsPage;
use Chrmrtns\KeylessAuth\Admin\Pages\OptionsPage;
use Chrmrtns\KeylessAuth\Admin\Pages\TwoFAUsersPage;
use Chrmrtns\KeylessAuth\Admin\Pages\HelpPage;

class MenuManager {

    /**
     * Page instances
     */
    private $dashboard_page;
    private $templates_page;
    private $smtp_page;
    private $mail_logs_page;
    private $options_page;
    private $twofa_users_page;
    private $help_page;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize page instances
        $this->dashboard_page = new DashboardPage();
        $this->templates_page = new TemplatesPage();
        $this->smtp_page = new SmtpPage();
        $this->mail_logs_page = new MailLogsPage();
        $this->options_page = new OptionsPage();
        $this->twofa_users_page = new TwoFAUsersPage();
        $this->help_page = new HelpPage();

        add_action('admin_menu', array($this, 'register_menus'));
    }

    /**
     * Register admin menus
     */
    public function register_menus() {
        // Main menu
        add_menu_page(
            __('Keyless Auth', 'keyless-auth'),
            __('Keyless Auth', 'keyless-auth'),
            'manage_options',
            'keyless-auth',
            array($this->dashboard_page, 'render'),
            'dashicons-shield-alt',
            30
        );

        // Templates submenu
        add_submenu_page(
            'keyless-auth',
            __('Email Templates', 'keyless-auth'),
            __('Templates', 'keyless-auth'),
            'manage_options',
            'keyless-auth-settings',
            array($this->templates_page, 'render')
        );

        // SMTP submenu
        add_submenu_page(
            'keyless-auth',
            __('SMTP Settings', 'keyless-auth'),
            __('SMTP', 'keyless-auth'),
            'manage_options',
            'chrmrtns-kla-smtp-settings',
            array($this->smtp_page, 'render')
        );

        // Mail Logs submenu
        add_submenu_page(
            'keyless-auth',
            __('Mail Logs', 'keyless-auth'),
            __('Mail Logs', 'keyless-auth'),
            'manage_options',
            'chrmrtns-mail-logs',
            array($this->mail_logs_page, 'render')
        );

        // Options submenu
        add_submenu_page(
            'keyless-auth',
            __('Options', 'keyless-auth'),
            __('Options', 'keyless-auth'),
            'manage_options',
            'keyless-auth-options',
            array($this->options_page, 'render')
        );

        // 2FA Users submenu
        add_submenu_page(
            'keyless-auth',
            __('2FA User Management', 'keyless-auth'),
            __('2FA Users', 'keyless-auth'),
            'manage_options',
            'keyless-auth-2fa-users',
            array($this->twofa_users_page, 'render')
        );

        // Help submenu
        add_submenu_page(
            'keyless-auth',
            __('Help & Instructions', 'keyless-auth'),
            __('Help', 'keyless-auth'),
            'manage_options',
            'keyless-auth-help',
            array($this->help_page, 'render')
        );
    }

    /**
     * Get page instance by slug
     */
    public function get_page($slug) {
        $pages = array(
            'dashboard' => $this->dashboard_page,
            'templates' => $this->templates_page,
            'smtp' => $this->smtp_page,
            'mail-logs' => $this->mail_logs_page,
            'options' => $this->options_page,
            'twofa-users' => $this->twofa_users_page,
            'help' => $this->help_page,
        );

        return isset($pages[$slug]) ? $pages[$slug] : null;
    }
}

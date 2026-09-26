<?php
/**
 * Plugin Name: MUTQAN Unified System
 * Description: Unified maintenance operations core for WordPress.
 * Version: 0.6.4
 * Requires PHP: 7.4
 */
defined('ABSPATH') || exit;

define('MUTQAN_VERSION', '0.6.4');
define('MUTQAN_FILE', __FILE__);
define('MUTQAN_DIR', plugin_dir_path(__FILE__));

require_once MUTQAN_DIR . 'src/Core/class-mutqan-core.php';
require_once MUTQAN_DIR . 'src/Core/class-mutqan-installer.php';
require_once MUTQAN_DIR . 'src/Unit01/class-mutqan-users.php';
require_once MUTQAN_DIR . 'src/Unit01/class-mutqan-audit.php';
require_once MUTQAN_DIR . 'src/Unit01/class-mutqan-admin.php';
require_once MUTQAN_DIR . 'src/Unit01/bootstrap.php';

register_activation_hook(MUTQAN_FILE, 'mutqan_activate_plugin');

function mutqan_activate_plugin() {
    MUTQAN_Core::register_roles();
    MUTQAN_Core::register_caps();
    update_option('mutqan_otp_enabled', 0, false);
    update_option('mutqan_activation_state', 'activated_pending_schema', false);
    update_option('mutqan_activation_diagnostic', array(
        'version' => MUTQAN_VERSION,
        'php' => PHP_VERSION,
        'wp' => function_exists('get_bloginfo') ? get_bloginfo('version') : '',
        'timestamp' => function_exists('current_time') ? current_time('mysql', true) : gmdate('Y-m-d H:i:s'),
    ), false);
    MUTQAN_Installer::mark_pending();
}

add_action('plugins_loaded', function() {
    require_once MUTQAN_DIR . 'src/bootstrap.php';
    require_once MUTQAN_DIR . 'src/Theme/class-mutqan-theme.php';
    MUTQAN_Core::boot();
    MUTQAN_Installer::maybe_install();
});

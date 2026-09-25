<?php
/**
 * Plugin Name: MUTQAN Unified System
 * Description: Unified maintenance operations core for WordPress.
 * Version: 0.1.1
 * Requires PHP: 7.4
 */
defined('ABSPATH') || exit;

define('MUTQAN_VERSION', '0.1.1');
define('MUTQAN_FILE', __FILE__);
define('MUTQAN_DIR', plugin_dir_path(__FILE__));

require_once MUTQAN_DIR . 'src/Core/class-mutqan-core.php';
require_once MUTQAN_DIR . 'src/Unit01/class-mutqan-users.php';
require_once MUTQAN_DIR . 'src/Unit01/class-mutqan-audit.php';
require_once MUTQAN_DIR . 'src/Unit01/class-mutqan-admin.php';
require_once MUTQAN_DIR . 'src/Unit01/bootstrap.php';
require_once MUTQAN_DIR . 'src/bootstrap.php';
require_once MUTQAN_DIR . 'src/Theme/class-mutqan-theme.php';

register_activation_hook(MUTQAN_FILE, array('MUTQAN_Unit01', 'activate'));

add_action('plugins_loaded', array('MUTQAN_Core', 'boot'));

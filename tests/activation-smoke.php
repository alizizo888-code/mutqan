<?php
define('ABSPATH', __DIR__ . '/wp-stubs/');
function plugin_dir_path($file) { return dirname($file) . '/'; }
function register_activation_hook($file, $callback) { $GLOBALS['mutqan_activation_callback'] = $callback; }
function add_action($hook, $callback) {}
function get_role($role) { return null; }
function add_role($role, $name, $caps) { return new class { public function add_cap($cap) {} }; }
function update_option($name, $value, $autoload = null) { $GLOBALS['mutqan_options'][$name] = $value; return true; }
function get_option($name, $default = false) { return $GLOBALS['mutqan_options'][$name] ?? $default; }
function current_time($type = 'mysql', $gmt = false) { return '2026-09-26 00:00:00'; }
function get_bloginfo($show = '') { return 'test'; }
require dirname(__DIR__) . '/mutqan.php';
if (!isset($GLOBALS['mutqan_activation_callback'])) { fwrite(STDERR, "activation hook was not registered\n"); exit(1); }
call_user_func($GLOBALS['mutqan_activation_callback']);
if (($GLOBALS['mutqan_options']['mutqan_activation_state'] ?? '') !== 'activated_pending_schema') { fwrite(STDERR, "activation state was not recorded\n"); exit(1); }
echo "ACTIVATION SMOKE PASSED\n";

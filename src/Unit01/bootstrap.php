<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/class-mutqan-users.php';
require_once __DIR__ . '/class-mutqan-audit.php';
require_once __DIR__ . '/class-mutqan-admin.php';

register_activation_hook(MUTQAN_FILE, array('MUTQAN_Unit01', 'activate'));

final class MUTQAN_Unit01 {
    public static function activate() {
        MUTQAN_Core::register_roles();
        MUTQAN_Core::register_caps();
        MUTQAN_Audit::install();
        update_option('mutqan_otp_enabled', 0, false);
    }
}

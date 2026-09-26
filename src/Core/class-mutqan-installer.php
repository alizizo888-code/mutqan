<?php
defined('ABSPATH') || exit;

final class MUTQAN_Installer {
    const SCHEMA_OPTION = 'mutqan_schema_version';
    const PENDING_OPTION = 'mutqan_schema_pending';

    public static function mark_pending() { update_option(self::PENDING_OPTION, 1, false); }

    public static function maybe_install() {
        $version = defined('MUTQAN_VERSION') ? MUTQAN_VERSION : '0';
        if (!get_option(self::PENDING_OPTION, false) && (string)get_option(self::SCHEMA_OPTION, '') === $version) return true;
        $errors = array();
        $installers = array('MUTQAN_Audit','MUTQAN_Operations','MUTQAN_GPS','MUTQAN_Communications','MUTQAN_Field_Execution','MUTQAN_Inventory','MUTQAN_Fleet','MUTQAN_Services','MUTQAN_Finance','MUTQAN_Billing','MUTQAN_Wallets','MUTQAN_Wallet_Topup_Requests','MUTQAN_Commissions','MUTQAN_Advances','MUTQAN_Accounting','MUTQAN_Expenses','MUTQAN_Settlements','MUTQAN_Warranty','MUTQAN_Ratings','MUTQAN_Pricing','MUTQAN_CRM','MUTQAN_Quality','MUTQAN_Marketing','MUTQAN_Promotions','MUTQAN_Notifications','MUTQAN_Content_SEO');
        foreach ($installers as $class) {
            if (!class_exists($class)) { $errors[$class] = 'installer_class_not_loaded'; continue; }
            if (!method_exists($class, 'install')) continue;
            try { call_user_func(array($class, 'install')); } catch (Throwable $e) { $errors[$class] = $e->getMessage(); }
        }
        update_option('mutqan_install_errors', $errors, false);
        if (!empty($errors)) { self::mark_pending(); return false; }
        update_option(self::SCHEMA_OPTION, $version, false);
        delete_option(self::PENDING_OPTION);
        update_option('mutqan_activation_state', 'ready', false);
        return true;
    }
}

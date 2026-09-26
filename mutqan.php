<?php
/**
 * Plugin Name: MUTQAN Unified System
 * Description: Unified maintenance operations core for WordPress.
 * Version: 0.6.2
 * Requires PHP: 7.4
 */
defined('ABSPATH') || exit;
define('MUTQAN_VERSION','0.6.2');
define('MUTQAN_FILE',__FILE__);
define('MUTQAN_DIR',plugin_dir_path(__FILE__));
require_once MUTQAN_DIR.'src/Core/class-mutqan-core.php';
require_once MUTQAN_DIR.'src/Unit01/class-mutqan-users.php';
require_once MUTQAN_DIR.'src/Unit01/class-mutqan-audit.php';
require_once MUTQAN_DIR.'src/Unit01/class-mutqan-admin.php';
require_once MUTQAN_DIR.'src/Unit01/bootstrap.php';
require_once MUTQAN_DIR.'src/bootstrap.php';
require_once MUTQAN_DIR.'src/Theme/class-mutqan-theme.php';
register_activation_hook(MUTQAN_FILE,'mutqan_activate_plugin');
function mutqan_activate_plugin(){
    MUTQAN_Unit01::activate();
    $installers=array(
        'MUTQAN_Operations','MUTQAN_GPS','MUTQAN_Communications','MUTQAN_Field_Execution','MUTQAN_Inventory','MUTQAN_Fleet',
        'MUTQAN_Services','MUTQAN_Finance','MUTQAN_Billing','MUTQAN_Wallets','MUTQAN_Wallet_Topup_Requests','MUTQAN_Commissions',
        'MUTQAN_Advances','MUTQAN_Accounting','MUTQAN_Expenses','MUTQAN_Settlements','MUTQAN_Warranty','MUTQAN_Ratings',
        'MUTQAN_Pricing','MUTQAN_CRM','MUTQAN_Quality','MUTQAN_Marketing','MUTQAN_Promotions'
    );
    foreach($installers as $class){
        if(class_exists($class) && method_exists($class,'install')) call_user_func(array($class,'install'));
    }
    if(class_exists('MUTQAN_Audit')) MUTQAN_Audit::install();
    if(class_exists('MUTQAN_Notifications') && method_exists('MUTQAN_Notifications','install')) MUTQAN_Notifications::install();
    if(class_exists('MUTQAN_Content_SEO') && method_exists('MUTQAN_Content_SEO','install')) MUTQAN_Content_SEO::install();
    flush_rewrite_rules();
}
add_action('plugins_loaded',array('MUTQAN_Core','boot'));

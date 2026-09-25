<?php
defined('ABSPATH') || exit;

final class MUTQAN_Audit {
    public static function init() {
        add_action('user_register', array(__CLASS__, 'user_registered'), 10, 1);
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'mutqan_audit';
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $table = self::table();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            action varchar(100) NOT NULL,
            object_type varchar(100) NOT NULL DEFAULT '',
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            context longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY object (object_type, object_id)
        ) {$charset};";
        dbDelta($sql);
    }

    public static function log($action, $object_type='', $object_id=0, $context=array()) {
        global $wpdb;
        $wpdb->insert(self::table(), array(
            'user_id' => get_current_user_id(),
            'action' => sanitize_key($action),
            'object_type' => sanitize_key($object_type),
            'object_id' => absint($object_id),
            'context' => wp_json_encode($context),
            'created_at' => current_time('mysql', true)
        ), array('%d','%s','%s','%d','%s','%s'));
    }

    public static function user_registered($user_id) {
        self::log('user_registered', 'user', $user_id, array('source' => get_user_meta($user_id, MUTQAN_Users::SOURCE_META, true)));
    }
}

MUTQAN_Audit::init();

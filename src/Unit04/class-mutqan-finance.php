<?php
defined('ABSPATH') || exit;
final class MUTQAN_Finance {
 public static function table(){global $wpdb;return $wpdb->prefix.'mutqan_finance';}
 public static function install(){global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$c=$wpdb->get_charset_collate();dbDelta("CREATE TABLE ".self::table()." (id bigint unsigned NOT NULL AUTO_INCREMENT,object_type varchar(40) NOT NULL,object_id bigint unsigned NOT NULL DEFAULT 0,kind varchar(40) NOT NULL,amount decimal(14,2) NOT NULL DEFAULT 0,currency varchar(8) NOT NULL DEFAULT 'SAR',status varchar(30) NOT NULL DEFAULT 'pending',payload longtext NULL,created_at datetime NOT NULL,PRIMARY KEY(id),KEY object(object_type,object_id),KEY status(status)) $c;");}
}

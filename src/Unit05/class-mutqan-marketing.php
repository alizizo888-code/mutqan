<?php
defined('ABSPATH') || exit;
final class MUTQAN_Marketing {
 public static function table(){global $wpdb;return $wpdb->prefix.'mutqan_campaigns';}
 public static function install(){global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$c=$wpdb->get_charset_collate();dbDelta("CREATE TABLE ".self::table()." (id bigint unsigned NOT NULL AUTO_INCREMENT,name varchar(190) NOT NULL,status varchar(30) NOT NULL DEFAULT 'draft',channel varchar(30) NOT NULL DEFAULT 'internal',content longtext NULL,start_at datetime NULL,end_at datetime NULL,created_at datetime NOT NULL,PRIMARY KEY(id),KEY status(status)) $c;");}
}

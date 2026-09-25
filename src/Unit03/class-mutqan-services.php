<?php
defined('ABSPATH') || exit;
final class MUTQAN_Services {
 public static function init(){add_action('rest_api_init',array(__CLASS__,'rest'));}
 public static function table(){global $wpdb;return $wpdb->prefix.'mutqan_services';}
 public static function install(){global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$c=$wpdb->get_charset_collate();dbDelta("CREATE TABLE ".self::table()." (id bigint unsigned NOT NULL AUTO_INCREMENT,name varchar(190) NOT NULL,slug varchar(190) NOT NULL,description text NULL,price decimal(12,2) NOT NULL DEFAULT 0,status varchar(20) NOT NULL DEFAULT 'active',meta longtext NULL,created_at datetime NOT NULL,PRIMARY KEY(id),UNIQUE KEY slug(slug)) $c;");}
 public static function rest(){register_rest_route('mutqan/v1','/services',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function(){global $wpdb;return rest_ensure_response($wpdb->get_results("SELECT id,name,slug,description,price,status FROM ".self::table()." WHERE status='active' ORDER BY id DESC",ARRAY_A));}));}
}
MUTQAN_Services::init();

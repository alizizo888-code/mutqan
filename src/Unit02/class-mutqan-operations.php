<?php
defined('ABSPATH') || exit;
final class MUTQAN_Operations {
    public static function init() { add_action('rest_api_init', array(__CLASS__,'rest')); }
    public static function table() { global $wpdb; return $wpdb->prefix.'mutqan_operations'; }
    public static function install() {
        global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php'; $c=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE ".self::table()." (id bigint unsigned NOT NULL AUTO_INCREMENT, type varchar(40) NOT NULL, status varchar(40) NOT NULL DEFAULT 'new', customer_id bigint unsigned NOT NULL DEFAULT 0, technician_id bigint unsigned NOT NULL DEFAULT 0, lat decimal(10,7) NULL, lng decimal(10,7) NULL, priority varchar(20) NOT NULL DEFAULT 'normal', payload longtext NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY(id), KEY status(status), KEY tech(technician_id), KEY customer(customer_id)) $c;");
    }
    public static function rest() {
        register_rest_route('mutqan/v1','/operations',array('methods'=>'POST','permission_callback'=>function(){return current_user_can('mutqan_add_orders')||current_user_can('mutqan_manage_operations');},'callback'=>function(WP_REST_Request $r){
            global $wpdb; $p=$r->get_json_params(); $now=current_time('mysql',true);
            $wpdb->insert(self::table(),array('type'=>sanitize_key($p['type']??'service'),'status'=>'new','customer_id'=>absint($p['customer_id']??get_current_user_id()),'technician_id'=>absint($p['technician_id']??0),'lat'=>isset($p['lat'])?(float)$p['lat']:null,'lng'=>isset($p['lng'])?(float)$p['lng']:null,'priority'=>sanitize_key($p['priority']??'normal'),'payload'=>wp_json_encode($p),'created_at'=>$now,'updated_at'=>$now));
            return rest_ensure_response(array('id'=>(int)$wpdb->insert_id,'status'=>'new'));
        }));
    }
}
MUTQAN_Operations::init();

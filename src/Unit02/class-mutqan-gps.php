<?php
defined('ABSPATH') || exit;

final class MUTQAN_GPS {
    public static function init(){ add_action('rest_api_init',array(__CLASS__,'rest')); }
    public static function table(){ global $wpdb; return $wpdb->prefix.'mutqan_gps_logs'; }

    public static function install(){
        global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php'; $c=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE ".self::table()." (id bigint unsigned NOT NULL AUTO_INCREMENT, technician_id bigint unsigned NOT NULL, order_id bigint unsigned NOT NULL DEFAULT 0, lat decimal(10,7) NOT NULL, lng decimal(10,7) NOT NULL, accuracy decimal(10,2) NULL, recorded_at datetime NOT NULL, PRIMARY KEY(id), KEY tech(technician_id), KEY order_id(order_id), KEY recorded_at(recorded_at)) $c;");
    }

    public static function log($tech_id,$lat,$lng,$accuracy=null,$order_id=0){
        global $wpdb;
        $lat=(float)$lat;$lng=(float)$lng;
        if($lat<-90||$lat>90||$lng<-180||$lng>180)return new WP_Error('invalid_coordinates','Invalid GPS coordinates.');
        $wpdb->insert(self::table(),array('technician_id'=>$tech_id,'order_id'=>absint($order_id),'lat'=>$lat,'lng'=>$lng,'accuracy'=>$accuracy===null?null:(float)$accuracy,'recorded_at'=>current_time('mysql',true)),array('%d','%d','%f','%f','%f','%s'));
        update_user_meta($tech_id,MUTQAN_Dispatch::LAT_META,$lat);
        update_user_meta($tech_id,MUTQAN_Dispatch::LNG_META,$lng);
        return (int)$wpdb->insert_id;
    }

    public static function rest(){
        register_rest_route('mutqan/v1','/gps/location',array(
            'methods'=>WP_REST_Server::CREATABLE,'permission_callback'=>function(){return current_user_can('mutqan_edit_own_profile')&&in_array('mutqan_technician',(array)wp_get_current_user()->roles,true);},
            'callback'=>function($r){
                $p=$r->get_json_params();$id=self::log(get_current_user_id(),$p['lat']??null,$p['lng']??null,$p['accuracy']??null,$p['order_id']??0);
                if(is_wp_error($id))return $id;
                return rest_ensure_response(array('ok'=>true,'log_id'=>$id));
            }
        ));
        register_rest_route('mutqan/v1','/orders/(?P<id>\d+)/technician-location',array(
            'methods'=>WP_REST_Server::READABLE,'permission_callback'=>function($r){
                $id=(int)$r['id']; global $wpdb;
                $o=$wpdb->get_row($wpdb->prepare("SELECT customer_id,technician_id FROM ".MUTQAN_Operations::table()." WHERE id=%d",$id),ARRAY_A);
                if(!$o)return false;
                return current_user_can('mutqan_manage_operations')||get_current_user_id()==(int)$o['customer_id']||get_current_user_id()==(int)$o['technician_id'];
            },
            'callback'=>function($r){
                global $wpdb;$id=(int)$r['id'];
                $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::table()." WHERE order_id=%d ORDER BY id DESC LIMIT 1",$id),ARRAY_A);
                if(!$row)return rest_ensure_response(array('available'=>false));
                return rest_ensure_response(array('available'=>true,'lat'=>(float)$row['lat'],'lng'=>(float)$row['lng'],'accuracy'=>$row['accuracy']===null?null:(float)$row['accuracy'],'recorded_at'=>$row['recorded_at']));
            }
        ));
    }
}
MUTQAN_GPS::init();

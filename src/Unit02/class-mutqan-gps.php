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
        $lat=(float)$lat; $lng=(float)$lng;
        if($lat<-90||$lat>90||$lng<-180||$lng>180)return new WP_Error('invalid_coordinates','Invalid GPS coordinates.');
        $order_id=absint($order_id);
        $wpdb->insert(self::table(),array(
            'technician_id'=>$tech_id,'order_id'=>$order_id,'lat'=>$lat,'lng'=>$lng,
            'accuracy'=>$accuracy===null?null:(float)$accuracy,'recorded_at'=>current_time('mysql',true)
        ),array('%d','%d','%f','%f','%f','%s'));
        update_user_meta($tech_id,MUTQAN_Dispatch::LAT_META,$lat);
        update_user_meta($tech_id,MUTQAN_Dispatch::LNG_META,$lng);
        return (int)$wpdb->insert_id;
    }

    private static function distance_km($a,$b,$c,$d){
        if($a===null||$b===null||$c===null||$d===null)return null;
        $r=6371; $dlat=deg2rad($c-$a); $dlng=deg2rad($d-$b);
        $x=sin($dlat/2)**2+cos(deg2rad($a))*cos(deg2rad($c))*sin($dlng/2)**2;
        return $r*(2*atan2(sqrt($x),sqrt(max(0,1-$x))));
    }

    private static function order_for_user($id,$uid){
        global $wpdb;
        $o=$wpdb->get_row($wpdb->prepare("SELECT customer_id,technician_id,lat,lng,status FROM ".MUTQAN_Operations::table()." WHERE id=%d",$id),ARRAY_A);
        if(!$o)return null;
        $allowed=current_user_can('mutqan_manage_operations')||(int)$o['customer_id']===$uid||(int)$o['technician_id']===$uid;
        return $allowed?$o:null;
    }

    public static function rest(){
        register_rest_route('mutqan/v1','/gps/location',array(
            'methods'=>WP_REST_Server::CREATABLE,
            'permission_callback'=>function(){return current_user_can('mutqan_edit_own_profile')&&in_array('mutqan_technician',(array)wp_get_current_user()->roles,true);},
            'callback'=>function($r){
                $p=$r->get_json_params();
                $id=self::log(get_current_user_id(),$p['lat']??null,$p['lng']??null,$p['accuracy']??null,$p['order_id']??0);
                if(is_wp_error($id))return $id;
                return rest_ensure_response(array('ok'=>true,'log_id'=>$id));
            }
        ));

        register_rest_route('mutqan/v1','/orders/(?P<id>\d+)/technician-location',array(
            'methods'=>WP_REST_Server::READABLE,
            'permission_callback'=>function($r){
                return self::order_for_user((int)$r['id'],get_current_user_id())!==null;
            },
            'callback'=>function($r){
                global $wpdb; $id=(int)$r['id'];
                $order=self::order_for_user($id,get_current_user_id());
                if(!$order)return new WP_Error('not_found','Order not found.',array('status'=>404));
                $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::table()." WHERE order_id=%d ORDER BY id DESC LIMIT 1",$id),ARRAY_A);
                if(!$row)return rest_ensure_response(array('available'=>false,'order_id'=>$id));
                $distance=self::distance_km((float)$row['lat'],(float)$row['lng'],$order['lat']===null?null:(float)$order['lat'],$order['lng']===null?null:(float)$order['lng']);
                $speed=(float)get_option('mutqan_eta_speed_kmh',35);
                $eta=$distance===null?null:(int)ceil(($distance/max(10,$speed))*60);
                return rest_ensure_response(array(
                    'available'=>true,'order_id'=>$id,'lat'=>(float)$row['lat'],'lng'=>(float)$row['lng'],
                    'accuracy'=>$row['accuracy']===null?null:(float)$row['accuracy'],'recorded_at'=>$row['recorded_at'],
                    'order_lat'=>$order['lat']===null?null:(float)$order['lat'],'order_lng'=>$order['lng']===null?null:(float)$order['lng'],
                    'distance_km'=>$distance===null?null:round($distance,2),'eta_minutes'=>$eta,'eta_type'=>'straight_line_estimate'
                ));
            }
        ));

        register_rest_route('mutqan/v1','/orders/(?P<id>\d+)/gps-history',array(
            'methods'=>WP_REST_Server::READABLE,
            'permission_callback'=>function($r){return self::order_for_user((int)$r['id'],get_current_user_id())!==null;},
            'callback'=>function($r){
                global $wpdb; $id=(int)$r['id']; $limit=min(500,max(10,absint($r->get_param('limit')?:200)));
                $rows=$wpdb->get_results($wpdb->prepare("SELECT lat,lng,accuracy,recorded_at FROM ".self::table()." WHERE order_id=%d ORDER BY id DESC LIMIT %d",$id,$limit),ARRAY_A);
                $rows=array_reverse($rows);
                return rest_ensure_response(array_map(function($x){$x['lat']=(float)$x['lat'];$x['lng']=(float)$x['lng'];$x['accuracy']=$x['accuracy']===null?null:(float)$x['accuracy'];return $x;},$rows));
            }
        ));
    }
}
MUTQAN_GPS::init();

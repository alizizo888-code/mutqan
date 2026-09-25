<?php
defined('ABSPATH') || exit;

final class MUTQAN_Dispatch {
    const LAT_META='_mutqan_lat';
    const LNG_META='_mutqan_lng';
    const STATUS_META='_mutqan_work_status';
    const CAPACITY_META='_mutqan_order_capacity';
    const SPECIALTIES_META='_mutqan_specialties';

    public static function init(){ add_action('rest_api_init',array(__CLASS__,'rest')); }

    public static function technician_snapshot($id){
        $u=get_userdata($id);
        if(!$u || !MUTQAN_Users::technician_can_receive($id)) return null;
        $specialties=(array)get_user_meta($id,self::SPECIALTIES_META,true);
        $capacity=max(1,absint(get_user_meta($id,self::CAPACITY_META,true)?:1));
        $active=self::active_orders($id);
        return array(
            'id'=>(int)$id,'name'=>$u->display_name,
            'status'=>sanitize_key(get_user_meta($id,self::STATUS_META,true)?:'available'),
            'lat'=>(float)get_user_meta($id,self::LAT_META,true),
            'lng'=>(float)get_user_meta($id,self::LNG_META,true),
            'capacity'=>$capacity,'active_orders'=>$active,'available_capacity'=>max(0,$capacity-$active),
            'specialties'=>array_values(array_filter(array_map('sanitize_key',$specialties)))
        );
    }

    public static function active_orders($tech_id){
        global $wpdb;
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM ".MUTQAN_Operations::table()." WHERE technician_id=%d AND status IN ('assigned','accepted','en_route','nearby','arrived','working','waiting_customer')",
            $tech_id
        ));
    }

    public static function distance_km($lat1,$lng1,$lat2,$lng2){
        if(!$lat1 || !$lng1 || !$lat2 || !$lng2)return null;
        $r=6371; $dlat=deg2rad($lat2-$lat1); $dlng=deg2rad($lng2-$lng1);
        $a=sin($dlat/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dlng/2)**2;
        return $r*(2*atan2(sqrt($a),sqrt(max(0,1-$a))));
    }

    public static function candidates($order_id){
        global $wpdb;
        $o=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".MUTQAN_Operations::table()." WHERE id=%d",$order_id),ARRAY_A);
        if(!$o)return new WP_Error('not_found','Order not found.');
        $payload=$o['payload']?json_decode($o['payload'],true):array();
        $needed=sanitize_key($payload['specialty']??$o['type']??'service');
        $users=get_users(array('role'=>'mutqan_technician','fields'=>'ID','number'=>200));
        $result=array();
        foreach($users as $id){
            $s=self::technician_snapshot($id); if(!$s)continue;
            if($s['status']!=='available' && $s['available_capacity']<1)continue;
            if($s['available_capacity']<1)continue;
            if($needed && $needed!=='service' && !empty($s['specialties']) && !in_array($needed,$s['specialties'],true))continue;
            $s['distance_km']=self::distance_km((float)$o['lat'],(float)$o['lng'],$s['lat'],$s['lng']);
            $priority_weight=array('urgent'=>-50,'high'=>-20,'normal'=>0,'low'=>10);\n            $s['score']=($s['distance_km']===null?9999:$s['distance_km'])+($priority_weight[sanitize_key($o['priority'])]??0);
            $result[]=$s;
        }
        usort($result,function($a,$b){return $a['score']<=>$b['score'];});
        return $result;
    }

    public static function assign($order_id,$tech_id,$automatic=false){
        if(!MUTQAN_Users::technician_can_receive($tech_id))return new WP_Error('technician_not_approved','Technician is not approved.');
        $s=self::technician_snapshot($tech_id);
        if(!$s || $s['available_capacity']<1)return new WP_Error('technician_unavailable','Technician has no available capacity.');
        global $wpdb;
        $o=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".MUTQAN_Operations::table()." WHERE id=%d",$order_id),ARRAY_A);
        if(!$o)return new WP_Error('not_found','Order not found.');
        if(!in_array($o['status'],array('new','pending_assignment','declined'),true))return new WP_Error('invalid_status','Order is not assignable in its current state.');
        $wpdb->update(MUTQAN_Operations::table(),array('technician_id'=>$tech_id,'updated_at'=>current_time('mysql',true)),array('id'=>$order_id),array('%d','%s'),array('%d'));\n        if(in_array($o['status'],array('new','declined'),true))MUTQAN_Orders::transition($order_id,'pending_assignment');\n        $transition=MUTQAN_Orders::transition($order_id,'assigned');\n        if(is_wp_error($transition))return $transition;
        MUTQAN_Audit::log($automatic?'order_auto_assigned':'order_assigned','order',$order_id,array('technician_id'=>$tech_id,'automatic'=>(bool)$automatic));
        MUTQAN_Events::emit('order_assigned',array('order_id'=>(int)$order_id,'technician_id'=>(int)$tech_id,'automatic'=>(bool)$automatic));
        return true;
    }

    public static function rest(){
        register_rest_route('mutqan/v1','/technicians/me/status',array(
            'methods'=>WP_REST_Server::EDITABLE,'permission_callback'=>function(){return current_user_can('mutqan_edit_own_profile') && in_array('mutqan_technician',(array)wp_get_current_user()->roles,true);},
            'callback'=>function($r){
                $p=$r->get_json_params(); $status=sanitize_key($p['status']??'available');
                if(!in_array($status,array('available','busy','offline'),true))return new WP_Error('invalid_status','Invalid technician status.',array('status'=>400));
                $id=get_current_user_id(); update_user_meta($id,self::STATUS_META,$status);
                if(isset($p['lat']))update_user_meta($id,self::LAT_META,(float)$p['lat']);
                if(isset($p['lng']))update_user_meta($id,self::LNG_META,(float)$p['lng']);
                if(isset($p['capacity']))update_user_meta($id,self::CAPACITY_META,max(1,min(20,absint($p['capacity']))));
                if(isset($p['specialties'])&&is_array($p['specialties']))update_user_meta($id,self::SPECIALTIES_META,array_values(array_filter(array_map('sanitize_key',$p['specialties']))));
                MUTQAN_Audit::log('technician_status_updated','user',$id,array('status'=>$status));
                return rest_ensure_response(self::technician_snapshot($id));
            }
        ));
        register_rest_route('mutqan/v1','/dispatch/candidates/(?P<id>\d+)',array(
            'methods'=>WP_REST_Server::READABLE,'permission_callback'=>function(){return current_user_can('mutqan_manage_operations');},
            'callback'=>function($r){$x=self::candidates((int)$r['id']);return is_wp_error($x)?$x:rest_ensure_response($x);}
        ));
        register_rest_route('mutqan/v1','/dispatch/assign/(?P<id>\d+)',array(
            'methods'=>WP_REST_Server::EDITABLE,'permission_callback'=>function(){return current_user_can('mutqan_manage_operations');},
            'callback'=>function($r){$p=$r->get_json_params();$ok=self::assign((int)$r['id'],absint($p['technician_id']??0),false);return is_wp_error($ok)?$ok:rest_ensure_response(array('ok'=>true,'order_id'=>(int)$r['id'],'technician_id'=>absint($p['technician_id'])));}
        ));
        register_rest_route('mutqan/v1','/dispatch/auto/(?P<id>\d+)',array(
            'methods'=>WP_REST_Server::EDITABLE,'permission_callback'=>function(){return current_user_can('mutqan_manage_operations');},
            'callback'=>function($r){$list=self::candidates((int)$r['id']);if(is_wp_error($list))return $list;if(empty($list))return new WP_Error('no_candidate','No suitable approved technician is available.',array('status'=>409));$ok=self::assign((int)$r['id'],(int)$list[0]['id'],true);return is_wp_error($ok)?$ok:rest_ensure_response(array('ok'=>true,'technician'=>$list[0]));}
        ));
    }
}
MUTQAN_Dispatch::init();

<?php
defined('ABSPATH') || exit;

final class MUTQAN_Orders {
    const STATUSES=array('new','pending_assignment','assigned','accepted','declined','en_route','nearby','arrived','working','waiting_customer','completed','cancelled','closed');

    public static function init(){ add_action('rest_api_init',array(__CLASS__,'rest')); }

    public static function table(){ return MUTQAN_Operations::table(); }

    public static function can_view($id){
        global $wpdb; $o=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::table()." WHERE id=%d",$id),ARRAY_A);
        if(!$o) return false;
        if(current_user_can('mutqan_manage_operations')||current_user_can('mutqan_view_orders')) return true;
        $uid=get_current_user_id();
        return $uid && ((int)$o['customer_id']===$uid || (int)$o['technician_id']===$uid && current_user_can('mutqan_view_own_orders'));
    }

    public static function transition($id,$status,$actor=null){
        global $wpdb;
        $status=sanitize_key($status);
        if(!in_array($status,self::STATUSES,true)) return new WP_Error('invalid_status','Invalid order status.');
        $old=$wpdb->get_var($wpdb->prepare("SELECT status FROM ".self::table()." WHERE id=%d",$id));
        if($old===null) return new WP_Error('not_found','Order not found.');
        $wpdb->update(self::table(),array('status'=>$status,'updated_at'=>current_time('mysql',true)),array('id'=>$id),array('%s','%s'),array('%d'));
        MUTQAN_Audit::log('order_status_changed','order',$id,array('from'=>$old,'to'=>$status,'actor'=>$actor?:get_current_user_id()));
        MUTQAN_Events::emit('order_status_changed',array('order_id'=>(int)$id,'from'=>$old,'to'=>$status));
        return true;
    }

    public static function rest(){
        register_rest_route('mutqan/v1','/orders/(?P<id>\d+)',array(
            'methods'=>WP_REST_Server::READABLE,
            'permission_callback'=>function($r){return self::can_view((int)$r['id']);},
            'callback'=>function($r){global $wpdb;$o=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::table()." WHERE id=%d",(int)$r['id']),ARRAY_A);if(!$o)return new WP_Error('not_found','Order not found.',array('status'=>404));$o['payload']=$o['payload']?json_decode($o['payload'],true):array();return rest_ensure_response($o);}
        ));
        register_rest_route('mutqan/v1','/orders/(?P<id>\d+)/status',array(
            'methods'=>WP_REST_Server::EDITABLE,
            'permission_callback'=>function($r){
                $id=(int)$r['id']; $p=wp_get_current_user();
                if(current_user_can('mutqan_manage_operations')||current_user_can('mutqan_edit_orders')) return true;
                return self::can_view($id) && in_array('mutqan_technician',(array)$p->roles,true);
            },
            'callback'=>function($r){
                $p=$r->get_json_params(); $status=sanitize_key($p['status']??'');
                $allowed=array('accepted','declined','en_route','nearby','arrived','working','waiting_customer','completed','cancelled');
                if(!in_array($status,$allowed,true))return new WP_Error('forbidden_transition','This role cannot set this status.',array('status'=>403));
                $ok=self::transition((int)$r['id'],$status);
                if(is_wp_error($ok))return $ok;
                return rest_ensure_response(array('id'=>(int)$r['id'],'status'=>$status));
            }
        ));
    }
}
MUTQAN_Orders::init();

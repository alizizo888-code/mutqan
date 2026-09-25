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

    public static function allowed_transition($from,$to){
        $map=array(
            'new'=>array('pending_assignment','cancelled'),
            'pending_assignment'=>array('assigned','cancelled'),
            'assigned'=>array('accepted','declined','cancelled'),
            'accepted'=>array('en_route','cancelled'),
            'declined'=>array('pending_assignment','cancelled'),
            'en_route'=>array('nearby','arrived','cancelled'),
            'nearby'=>array('arrived','cancelled'),
            'arrived'=>array('working','waiting_customer','cancelled'),
            'waiting_customer'=>array('working','cancelled'),
            'working'=>array('completed','waiting_customer','cancelled'),
            'completed'=>array('closed'),
            'closed'=>array(), 'cancelled'=>array()
        );
        return isset($map[$from]) && in_array($to,$map[$from],true);
    }

    public static function transition($id,$status,$actor=null){
        global $wpdb;
        $status=sanitize_key($status);
        if(!in_array($status,self::STATUSES,true)) return new WP_Error('invalid_status','Invalid order status.');
        $old=$wpdb->get_var($wpdb->prepare("SELECT status FROM ".self::table()." WHERE id=%d",$id));
        if($old===null) return new WP_Error('not_found','Order not found.');
        if(!self::allowed_transition($old,$status)) return new WP_Error('invalid_transition','Invalid order status transition.');
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
                global $wpdb; $o=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::table()." WHERE id=%d",$id),ARRAY_A);
                return $o && (int)$o['technician_id']===get_current_user_id() && MUTQAN_Users::technician_can_receive(get_current_user_id());
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

        register_rest_route('mutqan/v1','/public/orders',array(
            'methods'=>WP_REST_Server::CREATABLE,
            'permission_callback'=>'__return_true',
            'callback'=>function($r){
                $p=$r->get_json_params();
                $name=sanitize_text_field($p['name']??'');
                $phone=MUTQAN_Users::normalize_phone($p['phone']??'');
                if(strlen($name)<2||strlen(preg_replace('/\\D/','',$phone))<8)return new WP_Error('invalid_identity','Name and valid phone are required.',array('status'=>400));
                $ip=isset($_SERVER['REMOTE_ADDR'])?sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])):'unknown';
                $key='mutqan_public_order_'.md5($ip);
                $count=(int)get_transient($key);
                if($count>=5)return new WP_Error('rate_limited','Too many requests. Please try again later.',array('status'=>429));
                set_transient($key,$count+1,HOUR_IN_SECONDS);
                $customer=MUTQAN_Users::create_customer($name,$phone,'visitor');
                if(is_wp_error($customer))return $customer;
                global $wpdb; $now=current_time('mysql',true);
                $payload=array(
                    'source'=>'visitor','service'=>sanitize_text_field($p['service']??''),'details'=>sanitize_textarea_field($p['details']??''),
                    'location_label'=>sanitize_text_field($p['location_label']??'')
                );
                $wpdb->insert(self::table(),array('type'=>'service','status'=>'new','customer_id'=>(int)$customer,'technician_id'=>0,'lat'=>isset($p['lat'])?(float)$p['lat']:null,'lng'=>isset($p['lng'])?(float)$p['lng']:null,'priority'=>sanitize_key($p['priority']??'normal'),'payload'=>wp_json_encode($payload),'created_at'=>$now,'updated_at'=>$now));
                $id=(int)$wpdb->insert_id;
                MUTQAN_Audit::log('public_order_created','order',$id,array('customer_id'=>(int)$customer,'source'=>'visitor'));
                MUTQAN_Events::emit('order_created',array('order_id'=>$id,'customer_id'=>(int)$customer,'source'=>'visitor'));
                return rest_ensure_response(array('id'=>$id,'customer_id'=>(int)$customer,'status'=>'new'));
            }
        ));
    }
}
MUTQAN_Orders::init();

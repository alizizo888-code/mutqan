<?php
defined('ABSPATH') || exit;

final class MUTQAN_Communications {
    public static function init() {
        add_action('rest_api_init', array(__CLASS__,'rest'));
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix.'mutqan_messages';
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $c=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE ".self::table()." (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint unsigned NOT NULL,
            sender_id bigint unsigned NOT NULL,
            recipient_id bigint unsigned NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'text',
            body longtext NULL,
            attachment_id bigint unsigned NOT NULL DEFAULT 0,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            read_at datetime NULL,
            PRIMARY KEY(id),
            KEY order_id(order_id),
            KEY recipient(recipient_id,is_read),
            KEY created_at(created_at)
        ) $c;");
    }

    public static function participants($order_id) {
        global $wpdb;
        $o=$wpdb->get_row($wpdb->prepare(
            "SELECT customer_id,technician_id FROM ".MUTQAN_Operations::table()." WHERE id=%d",$order_id
        ),ARRAY_A);
        if(!$o)return false;
        $uid=get_current_user_id();
        return $uid && ($uid==(int)$o['customer_id'] || $uid==(int)$o['technician_id'] || current_user_can('mutqan_manage_operations'));
    }

    public static function send($order_id,$type,$body='',$attachment_id=0) {
        global $wpdb;
        $o=$wpdb->get_row($wpdb->prepare(
            "SELECT customer_id,technician_id,status FROM ".MUTQAN_Operations::table()." WHERE id=%d",$order_id
        ),ARRAY_A);
        if(!$o)return new WP_Error('not_found','Order not found.');
        $uid=get_current_user_id();
        if(!$uid || !self::participants($order_id))return new WP_Error('forbidden','Not an order participant.',array('status'=>403));
        if(in_array($o['status'],array('cancelled','closed'),true))return new WP_Error('order_closed','Communication is closed for this order.',array('status'=>409));
        $type=sanitize_key($type);
        if(!in_array($type,array('text','voice','image','file','system'),true))return new WP_Error('invalid_type','Invalid message type.');
        $recipient=($uid==(int)$o['customer_id'])?(int)$o['technician_id']:(int)$o['customer_id'];
        if(!$recipient && !current_user_can('mutqan_manage_operations'))return new WP_Error('no_recipient','No recipient assigned.');
        if($attachment_id && !self::attachment_allowed($attachment_id,$order_id,$uid))return new WP_Error('invalid_attachment','Attachment is not allowed.',array('status'=>400));
        if($type==='text') {
            $body=sanitize_textarea_field($body);
            if($body==='')return new WP_Error('empty_message','Message is empty.');
        }
        $wpdb->insert(self::table(),array(
            'order_id'=>$order_id,'sender_id'=>$uid,'recipient_id'=>$recipient,'type'=>$type,
            'body'=>$body,'attachment_id'=>absint($attachment_id),'is_read'=>0,'created_at'=>current_time('mysql',true)
        ),array('%d','%d','%d','%s','%s','%d','%d','%s'));
        $id=(int)$wpdb->insert_id;
        MUTQAN_Audit::log('message_sent','order',$order_id,array('message_id'=>$id,'type'=>$type));
        MUTQAN_Events::emit('message_sent',array('message_id'=>$id,'order_id'=>$order_id,'sender_id'=>$uid,'recipient_id'=>$recipient,'type'=>$type));
        return $id;
    }

    public static function attachment_allowed($attachment_id,$order_id,$user_id){
        $attachment_id=absint($attachment_id); if(!$attachment_id)return false;
        $post=get_post($attachment_id); if(!$post)return false;
        if($post->post_type!=='attachment')return false;
        $mime=get_post_mime_type($attachment_id);
        $allowed=array('image/jpeg','image/png','image/webp','application/pdf','audio/mpeg','audio/ogg','audio/webm');
        if(!in_array($mime,$allowed,true))return false;
        return current_user_can('mutqan_manage_operations') || (int)$post->post_author===$user_id;
    }

    public static function rest() {
        register_rest_route('mutqan/v1','/orders/(?P<id>\d+)/messages',array(
            'methods'=>WP_REST_Server::READABLE,
            'permission_callback'=>function($r){return self::participants((int)$r['id']);},
            'callback'=>function($r){
                global $wpdb;$uid=get_current_user_id();$id=(int)$r['id'];
                $rows=$wpdb->get_results($wpdb->prepare(
                    "SELECT id,sender_id,recipient_id,type,body,attachment_id,is_read,created_at,read_at FROM ".self::table()." WHERE order_id=%d ORDER BY id ASC LIMIT 300",$id
                ),ARRAY_A);
                return rest_ensure_response($rows);
            }
        ));
        register_rest_route('mutqan/v1','/orders/(?P<id>\d+)/messages',array(
            'methods'=>WP_REST_Server::CREATABLE,
            'permission_callback'=>function($r){return self::participants((int)$r['id']);},
            'callback'=>function($r){
                $p=$r->get_json_params();
                $id=self::send((int)$r['id'],$p['type']??'text',$p['body']??'',$p['attachment_id']??0);
                if(is_wp_error($id))return $id;
                return rest_ensure_response(array('ok'=>true,'message_id'=>$id));
            }
        ));
        register_rest_route('mutqan/v1','/orders/(?P<id>\d+)/messages/(?P<message_id>\d+)/read',array(
            'methods'=>WP_REST_Server::EDITABLE,
            'permission_callback'=>function($r){return self::participants((int)$r['id']);},
            'callback'=>function($r){
                global $wpdb;$id=(int)$r['message_id'];$uid=get_current_user_id();
                $wpdb->update(self::table(),array('is_read'=>1,'read_at'=>current_time('mysql',true)),array('id'=>$id,'recipient_id'=>$uid),array('%d','%s'),array('%d','%d'));
                return rest_ensure_response(array('ok'=>true));
            }
        ));
    }
}
MUTQAN_Communications::init();

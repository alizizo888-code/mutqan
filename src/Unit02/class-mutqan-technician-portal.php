<?php
defined('ABSPATH') || exit;

final class MUTQAN_Technician_Portal {
    public static function init(){ add_action('rest_api_init',array(__CLASS__,'rest')); add_shortcode('mutqan_technician_portal',array(__CLASS__,'shortcode')); add_action('wp_enqueue_scripts',array(__CLASS__,'assets'),45); }
    private static function allowed(){ return is_user_logged_in() && in_array('mutqan_technician',(array)wp_get_current_user()->roles,true) && MUTQAN_Users::technician_can_receive(get_current_user_id()); }
    public static function assets(){
        if(!is_singular())return; global $post;
        if(!$post || !has_shortcode($post->post_content,'mutqan_app'))return;
        wp_enqueue_style('mutqan-tech',plugins_url('assets/mutqan-technician.css',MUTQAN_FILE),array('mutqan-ui'),MUTQAN_VERSION);
        wp_enqueue_script('mutqan-tech',plugins_url('assets/mutqan-technician.js',MUTQAN_FILE),array(),MUTQAN_VERSION,true);
        wp_localize_script('mutqan-tech','MUTQAN_TECH',array('root'=>esc_url_raw(rest_url('mutqan/v1')),'nonce'=>wp_create_nonce('wp_rest')));
    }
    public static function rest(){
        register_rest_route('mutqan/v1','/technician/me',array(
            'methods'=>WP_REST_Server::READABLE,'permission_callback'=>array(__CLASS__,'allowed'),
            'callback'=>function(){ $id=get_current_user_id(); return rest_ensure_response(MUTQAN_Dispatch::technician_snapshot($id)); }
        ));
        register_rest_route('mutqan/v1','/technician/orders',array(
            'methods'=>WP_REST_Server::READABLE,'permission_callback'=>array(__CLASS__,'allowed'),
            'callback'=>function(){
                global $wpdb; $id=get_current_user_id();
                $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM ".MUTQAN_Operations::table()." WHERE technician_id=%d AND status NOT IN ('closed','cancelled') ORDER BY FIELD(priority,'urgent','high','normal','low'), id DESC LIMIT 100",$id),ARRAY_A);
                foreach($rows as &$o){$o['payload']=$o['payload']?json_decode($o['payload'],true):array();}
                return rest_ensure_response($rows);
            }
        ));
    }
    public static function shortcode(){
        if(!self::allowed())return '<div class="mq-tech-locked">حساب الفني غير معتمد بعد.</div>';
        return '<section class="mq-tech-portal"><div class="mq-tech-toolbar"><div><strong>طلبات الفني</strong><small data-tech-state>جاري التحميل...</small></div><button type="button" data-tech-refresh>تحديث</button></div><div class="mq-tech-status"><button data-tech-status="available">متاح</button><button data-tech-status="busy">مشغول</button><button data-tech-status="offline">غير متاح</button></div><div data-tech-orders class="mq-tech-orders">جاري التحميل...</div><div data-tech-detail class="mq-tech-detail" hidden></div></section>';
    }
}
MUTQAN_Technician_Portal::init();

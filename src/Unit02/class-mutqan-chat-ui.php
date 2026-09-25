<?php
defined('ABSPATH') || exit;

final class MUTQAN_Chat_UI {
    public static function init(){
        add_action('wp_enqueue_scripts',array(__CLASS__,'assets'),40);
        add_shortcode('mutqan_chat',array(__CLASS__,'shortcode'));
    }

    public static function assets(){
        if(!is_singular())return;
        global $post;
        if(!$post||!has_shortcode($post->post_content,'mutqan_chat'))return;
        wp_enqueue_style('mutqan-chat',plugins_url('src/Unit02/assets/mutqan-chat.css',MUTQAN_FILE),array('mutqan-theme'),MUTQAN_VERSION);
        wp_enqueue_script('mutqan-chat',plugins_url('src/Unit02/assets/mutqan-chat.js',MUTQAN_FILE),array(),MUTQAN_VERSION,true);
    }

    public static function shortcode($atts=array()){
        $atts=shortcode_atts(array('order_id'=>0),$atts,'mutqan_chat');
        $id=absint($atts['order_id']);
        if(!$id)return '<div class="mq-chat"><p>حدد رقم الطلب لفتح المحادثة.</p></div>';
        if(!MUTQAN_Communications::participants($id))return '<div class="mq-chat"><p>لا تملك صلاحية هذه المحادثة.</p></div>';
        wp_localize_script('mutqan-chat','MUTQAN_CHAT',array(
            'endpoint'=>esc_url_raw(rest_url('mutqan/v1/orders/'.$id.'/messages')),
            'base'=>esc_url_raw(rest_url('mutqan/v1/orders/'.$id.'/messages')),
            'nonce'=>wp_create_nonce('wp_rest'),
            'userId'=>get_current_user_id()
        ));
        return '<section class="mq-chat"><div class="mq-chat-head"><h3>محادثة الطلب #'.esc_html($id).'</h3><span class="mq-chip">مُتقِن</span></div><div class="mq-chat-messages" data-chat-messages>جاري التحميل...</div><form class="mq-chat-form" data-chat-form><button type="button" class="mq-attach" disabled>📎</button><input name="body" type="text" maxlength="2000" placeholder="اكتب رسالتك..."><button type="submit">إرسال</button></form></section>';
    }
}
MUTQAN_Chat_UI::init();

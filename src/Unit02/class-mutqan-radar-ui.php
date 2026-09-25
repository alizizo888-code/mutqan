<?php
defined('ABSPATH') || exit;

final class MUTQAN_Radar_UI {
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__,'assets'), 30);
        add_shortcode('mutqan_radar', array(__CLASS__,'shortcode'));
        add_action('admin_menu', array(__CLASS__,'menu'), 30);
    }

    public static function assets() {
        if (!is_singular()) return;
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'mutqan_radar')) return;
        wp_enqueue_style('leaflet','https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',array(), '1.9.4');
        wp_enqueue_script('leaflet','https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',array(), '1.9.4', true);
        wp_enqueue_style('mutqan-radar', plugins_url('src/Unit02/assets/mutqan-radar.css', MUTQAN_FILE), array('mutqan-theme','leaflet'), MUTQAN_VERSION);
        wp_enqueue_script('mutqan-radar', plugins_url('src/Unit02/assets/mutqan-radar.js', MUTQAN_FILE), array('leaflet'), MUTQAN_VERSION, true);
        wp_localize_script('mutqan-radar', 'MUTQAN_RADAR', array(
            'endpoint' => esc_url_raw(rest_url('mutqan/v1/radar')),
            'opsEndpoint' => esc_url_raw(rest_url('mutqan/v1/operations-room')),
            'assignEndpoint' => esc_url_raw(rest_url('mutqan/v1/operations-room/assign')),
            'nonce' => wp_create_nonce('wp_rest'),
            'tileUrl' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'tileAttribution' => '&copy; OpenStreetMap contributors',
            'refreshMs' => 10000
        ));
    }

    public static function shortcode() {
        if (!current_user_can('mutqan_manage_operations') && !current_user_can('mutqan_view_orders')) {
            return '<div class="mq-radar"><p>ليس لديك صلاحية لعرض الرادار التشغيلي.</p></div>';
        }
        return '<section class="mq-radar"><div class="mq-radar-head"><div><span class="mq-chip">مُتقِن</span><h2>الرادار التشغيلي</h2></div><button class="mq-radar-refresh" type="button" data-radar-refresh>تحديث</button></div><div class="mq-radar-stats" data-radar-summary></div><div class="mq-radar-map" data-radar-map></div><div class="mq-ops-toolbar"><button type="button" data-ops-load>غرفة العمليات المتقدمة</button><span data-ops-alerts></span></div><div class="mq-ops-board" data-ops-board></div><div class="mq-radar-board"><div class="mq-radar-panel"><h3>الفنيون</h3><div class="mq-radar-list" data-radar-techs></div></div><div class="mq-radar-panel"><h3>الطلبات المفتوحة</h3><div class="mq-radar-list" data-radar-orders></div></div></div></section>';
    }

    public static function menu() {
        add_submenu_page('mutqan','Operations Radar','Operations Radar','mutqan_manage_operations','mutqan-radar',array(__CLASS__,'admin_page'));
    }

    public static function admin_page() {
        echo '<div class="wrap"><h1>Operations Radar</h1><p>أنشئ صفحة WordPress تحتوي على <code>[mutqan_radar]</code> لعرض الرادار.</p></div>';
    }
}
MUTQAN_Radar_UI::init();

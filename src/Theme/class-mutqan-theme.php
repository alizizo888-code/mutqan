<?php
defined('ABSPATH') || exit;

final class MUTQAN_Theme {
    const OPTION = 'mutqan_theme';

    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'ui_assets'), 20);
        add_shortcode('mutqan_app', array(__CLASS__, 'shortcode'));
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_init', array(__CLASS__, 'settings'));
    }

    public static function defaults() {
        return array(
            'primary' => '#007A55',
            'primary_dark' => '#005A40',
            'accent' => '#F4B65F',
            'surface' => '#F5F7FC',
            'card' => '#FFFFFF',
            'text' => '#102033',
            'muted' => '#667085',
            'radius' => '22px',
            'logo_url' => '',
            'splash_enabled' => 1,
            'splash_duration' => 1800,
            'company_name' => 'مُتقِن',
            'tagline' => 'حلول الصيانة والمتابعة بسهولة وأمان'
        );
    }

    public static function get() {
        return wp_parse_args((array)get_option(self::OPTION, array()), self::defaults());
    }

    public static function ui_assets() {
        wp_enqueue_style('mutqan-ui', plugins_url('src/UI/assets/mutqan-ui.css', MUTQAN_FILE), array('mutqan-theme'), MUTQAN_VERSION);\n        wp_enqueue_script('mutqan-ui', plugins_url('src/UI/assets/mutqan-ui.js', MUTQAN_FILE), array(), MUTQAN_VERSION, true);
    }

    public static function assets() {
        wp_register_style('mutqan-theme', plugins_url('src/Theme/assets/mutqan-theme.css', MUTQAN_FILE), array(), MUTQAN_VERSION);
        wp_enqueue_style('mutqan-theme');

        $t = self::get();
        $css = ':root{--mq-primary:'.esc_attr($t['primary']).';--mq-primary-dark:'.esc_attr($t['primary_dark']).';--mq-accent:'.esc_attr($t['accent']).';--mq-surface:'.esc_attr($t['surface']).';--mq-card:'.esc_attr($t['card']).';--mq-text:'.esc_attr($t['text']).';--mq-muted:'.esc_attr($t['muted']).';--mq-radius:'.esc_attr($t['radius']).';}';
        wp_add_inline_style('mutqan-theme', $css);

        wp_enqueue_script('mutqan-theme', plugins_url('src/Theme/assets/mutqan-theme.js', MUTQAN_FILE), array(), MUTQAN_VERSION, true);
        wp_localize_script('mutqan-theme', 'MUTQAN_THEME', array(
            'splashEnabled' => (bool)$t['splash_enabled'],
            'duration' => absint($t['splash_duration'])
        ));
    }

    public static function shortcode($atts=array()) {
        return MUTQAN_App::render();
        $t = self::get();
        $role = 'visitor';
        if (is_user_logged_in()) {
            $u = wp_get_current_user();
            $role = !empty($u->roles) ? $u->roles[0] : 'customer';
        }
        ob_start();
        ?>
        <div class="mq-app" data-role="<?php echo esc_attr($role); ?>">
            <header class="mq-header">
                <div class="mq-brand">
                    <?php if (!empty($t['logo_url'])): ?><img src="<?php echo esc_url($t['logo_url']); ?>" alt="<?php echo esc_attr($t['company_name']); ?>"><?php endif; ?>
                    <div><strong><?php echo esc_html($t['company_name']); ?></strong><small><?php echo esc_html($t['tagline']); ?></small></div>
                </div>
                <button class="mq-icon-btn" type="button" aria-label="مساعدة">؟</button>
            </header>
            <section class="mq-welcome">
                <span class="mq-badge">مُتقِن</span>
                <h1>أهلاً بك 👋</h1>
                <p>خدمات واضحة، متابعة فورية، وتجربة آمنة.</p>
            </section>
            <div class="mq-search"><span>⌕</span><input type="search" placeholder="ابحث عن خدمة، تكييف، سباكة، كهرباء..."></div>
            <section class="mq-grid">
                <button class="mq-service"><span>❄</span><b>تكييف وتبريد</b><small>صيانة وتركيب وفحص</small></button>
                <button class="mq-service"><span>⚡</span><b>كهرباء</b><small>أعمال كهربائية وصيانة</small></button>
                <button class="mq-service"><span>🔧</span><b>سباكة</b><small>كشف وإصلاح وتركيب</small></button>
                <button class="mq-service"><span>▦</span><b>خدمات عامة</b><small>حلول صيانة متنوعة</small></button>
            </section>
            <section class="mq-hero">
                <div><span class="mq-chip">عرض مميز</span><h2>اطلب خدمتك الآن</h2><p>اختر الخدمة، حدد موقعك، وتابع الفني خطوة بخطوة.</p></div>
                <button type="button">ابدأ الطلب ←</button>
            </section>
            <nav class="mq-bottom-nav">
                <a href="#">الرئيسية</a><a href="#">طلباتي</a><a href="#">العروض</a><a href="#">المتاجر</a><a href="#">حسابي</a>
            </nav>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function menu() {
        add_submenu_page('mutqan','Appearance','Appearance','mutqan_manage_settings','mutqan-appearance',array(__CLASS__,'page'));
    }

    public static function settings() {
        register_setting('mutqan_theme_group', self::OPTION, array(
            'sanitize_callback' => function($input) {
                $d = self::defaults();
                $o = array();
                foreach (array('primary','primary_dark','accent','surface','card','text','muted') as $k) {
                    $o[$k] = isset($input[$k]) && preg_match('/^#[0-9a-fA-F]{6}$/', $input[$k]) ? $input[$k] : $d[$k];
                }
                $o['radius'] = isset($input['radius']) ? preg_replace('/[^0-9a-zA-Z.% ]/','',$input['radius']) : $d['radius'];
                $o['logo_url'] = isset($input['logo_url']) ? esc_url_raw($input['logo_url']) : '';
                $o['splash_enabled'] = !empty($input['splash_enabled']) ? 1 : 0;
                $o['splash_duration'] = max(800, min(5000, absint($input['splash_duration'] ?? 1800)));
                $o['company_name'] = sanitize_text_field($input['company_name'] ?? $d['company_name']);
                $o['tagline'] = sanitize_text_field($input['tagline'] ?? $d['tagline']);
                return $o;
            }
        ));
    }

    public static function page() {
        if (!current_user_can('mutqan_manage_settings')) wp_die('Unauthorized');
        $t = self::get();
        ?>
        <div class="wrap"><h1>MUTQAN — Theme & Splash</h1>
        <p>نفس الهوية البصرية لكل الواجهات، مع إمكانية تعديلها لاحقًا بدون تعديل الكود.</p>
        <form method="post" action="options.php"><?php settings_fields('mutqan_theme_group'); ?>
        <table class="form-table">
        <tr><th>Primary</th><td><input type="color" name="<?php echo esc_attr(self::OPTION); ?>[primary]" value="<?php echo esc_attr($t['primary']); ?>"></td></tr>
        <tr><th>Primary Dark</th><td><input type="color" name="<?php echo esc_attr(self::OPTION); ?>[primary_dark]" value="<?php echo esc_attr($t['primary_dark']); ?>"></td></tr>
        <tr><th>Accent</th><td><input type="color" name="<?php echo esc_attr(self::OPTION); ?>[accent]" value="<?php echo esc_attr($t['accent']); ?>"></td></tr>
        <tr><th>Surface</th><td><input type="color" name="<?php echo esc_attr(self::OPTION); ?>[surface]" value="<?php echo esc_attr($t['surface']); ?>"></td></tr>
        <tr><th>Logo URL</th><td><input class="regular-text" type="url" name="<?php echo esc_attr(self::OPTION); ?>[logo_url]" value="<?php echo esc_attr($t['logo_url']); ?>"></td></tr>
        <tr><th>Company name</th><td><input class="regular-text" type="text" name="<?php echo esc_attr(self::OPTION); ?>[company_name]" value="<?php echo esc_attr($t['company_name']); ?>"></td></tr>
        <tr><th>Tagline</th><td><input class="regular-text" type="text" name="<?php echo esc_attr(self::OPTION); ?>[tagline]" value="<?php echo esc_attr($t['tagline']); ?>"></td></tr>
        <tr><th>Splash</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[splash_enabled]" value="1" <?php checked($t['splash_enabled'],1); ?>> مفعلة</label></td></tr>
        <tr><th>Splash duration</th><td><input type="number" min="800" max="5000" name="<?php echo esc_attr(self::OPTION); ?>[splash_duration]" value="<?php echo esc_attr($t['splash_duration']); ?>"> ms</td></tr>
        </table><?php submit_button('حفظ الهوية'); ?></form></div>
        <?php
    }
}
MUTQAN_Theme::init();

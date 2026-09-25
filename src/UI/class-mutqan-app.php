<?php
defined('ABSPATH') || exit;

final class MUTQAN_App {
    public static function init() {
        add_shortcode('mutqan_app', array(__CLASS__,'render')); add_action('wp_enqueue_scripts', array(__CLASS__,'assets'));
        add_action('rest_api_init', array(__CLASS__,'rest'));
    }

    public static function assets() { wp_enqueue_style('mutqan-customer',plugins_url('src/Unit03/assets/mutqan-customer.css',MUTQAN_FILE),array('mutqan-ui'),MUTQAN_VERSION); wp_enqueue_script('mutqan-customer',plugins_url('src/Unit03/assets/mutqan-customer.js',MUTQAN_FILE),array(),MUTQAN_VERSION,true); wp_localize_script('mutqan-customer','mqCustomer',array('root'=>esc_url_raw(rest_url('mutqan/v1')),'loggedIn'=>is_user_logged_in())); }

    public static function role() {
        if (!is_user_logged_in()) return 'visitor';
        $u=wp_get_current_user();
        foreach (array('mutqan_owner','mutqan_site_admin','mutqan_operations','mutqan_technician','mutqan_customer','mutqan_partner') as $role) {
            if (in_array($role,(array)$u->roles,true)) return $role;
        }
        return 'visitor';
    }

    public static function render() {
        $role=self::role();
        $cfg=MUTQAN_Registry::role_config($role);
        $title=array(
            'visitor'=>'مرحبًا بك في مُتقِن',
            'mutqan_customer'=>'أهلاً بك في مُتقِن 👋',
            'mutqan_technician'=>'لوحة الفني',
            'mutqan_operations'=>'غرفة العمليات',
            'mutqan_site_admin'=>'لوحة الإشراف',
            'mutqan_owner'=>'مركز تحكم مُتقِن',
            'mutqan_partner'=>'بوابة شريك المهنة'
        );
        ob_start(); ?>
        <div class="mq-app mq-role-<?php echo esc_attr($role); ?>" data-role="<?php echo esc_attr($role); ?>">
            <section class="mq-surface">
                <div class="mq-app-head">
                    <div><span class="mq-chip">مُتقِن</span><h1><?php echo esc_html($title[$role]??'مُتقِن'); ?></h1><p>نفس الهوية، صلاحيات ومهام مختلفة حسب الدور.</p></div>
                </div>
                <?php if ($role==='visitor' || $role==='mutqan_customer'): ?>
                    <div class="mq-hero"><h2>خدمتك تبدأ من هنا</h2><p>اختر التخصص، ارفع الطلب، وحدد موقع الخدمة.</p><button type="button" data-mq-action="new-order">ابدأ طلب خدمة ←</button><div class="mq-portal-slot"><?php echo do_shortcode("[mutqan_customer_portal]"); ?></div></div>
                    <div class="mq-grid"><button class="mq-service">❄️<b>تكييف وتبريد</b><small>صيانة وتركيب وفحص</small></button><button class="mq-service">⚡<b>كهرباء</b><small>أعمال وصيانة</small></button><button class="mq-service">🔧<b>سباكة</b><small>كشف وإصلاح وتركيب</small></button><button class="mq-service">🛠️<b>صيانة عامة</b><small>حلول متعددة</small></button></div>
                <?php elseif ($role==='mutqan_technician'): ?>
                    <div class="mq-stat-row"><div><b>3</b><small>مهام اليوم</small></div><div><b>2</b><small>طلبات قريبة</small></div><div><b>420</b><small>ريال اليوم</small></div></div><div class="mq-hero"><h2>المهمة الجارية</h2><p>ابدأ التحرك ثم أكد الوصول من داخل الطلب.</p><button type="button" data-mq-action="open-task">فتح المهمة</button></div>
                <?php elseif ($role==='mutqan_operations' || $role==='mutqan_site_admin' || $role==='mutqan_owner'): ?>
                    <div class="mq-stat-row"><div><b>12</b><small>طلبات جديدة</small></div><div><b>8</b><small>فنيون متاحون</small></div><div><b>4</b><small>مهام عاجلة</small></div></div><div class="mq-hero"><h2>الرادار التشغيلي</h2><p>الطلبات والفنيون والحالات في شاشة واحدة.</p><button type="button" data-mq-action="radar">فتح الرادار ←</button></div>
                <?php else: ?>
                    <div class="mq-hero"><h2>بوابة الشريك</h2><p>تابع الطلبات والفرص المرتبطة بشراكتك.</p><button type="button">عرض الطلبات</button></div>
                <?php endif; ?>
            </section>
            <nav class="mq-bottom-nav"><?php foreach ((array)$cfg['nav'] as $item): ?><a href="#" data-mq-nav="<?php echo esc_attr($item); ?>"><?php echo esc_html(self::nav_label($item)); ?></a><?php endforeach; ?></nav>
        </div>
        <?php return ob_get_clean();
    }

    private static function nav_label($key) {
        $map=array('home'=>'الرئيسية','orders'=>'طلباتي','offers'=>'العروض','account'=>'حسابي','tasks'=>'المهام','wallet'=>'المحفظة','radar'=>'الرادار','operations'=>'العمليات','users'=>'المستخدمون','settings'=>'الإعدادات','audit'=>'التدقيق','services'=>'الخدمات','help'=>'المساعدة');
        return $map[$key]??$key;
    }

    public static function rest() {
        register_rest_route('mutqan/v1','/ui-config',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function(){
            $role=self::role(); return rest_ensure_response(array('role'=>$role,'theme'=>MUTQAN_Theme::get(),'registry'=>MUTQAN_Registry::role_config($role)));
        }));
    }
}
MUTQAN_App::init();

<?php
defined('ABSPATH') || exit;

final class MUTQAN_App {
    const CATALOG_OPTION = 'mutqan_service_catalog';

    public static function init() {
        add_shortcode('mutqan_app', array(__CLASS__, 'render'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'));
        add_action('admin_menu', array(__CLASS__, 'admin_menu'), 30);
        add_action('admin_post_mutqan_create_pages', array(__CLASS__, 'create_pages'));
        add_action('init', array(__CLASS__, 'seed'), 6);
        add_action('rest_api_init', array(__CLASS__, 'rest'));
    }

    public static function seed() {
        if (!get_option(self::CATALOG_OPTION, false)) update_option(self::CATALOG_OPTION, self::catalog_defaults(), false);
    }

    public static function assets() {
        if (!is_singular()) return;
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'mutqan_app')) return;
        wp_enqueue_style('mutqan-ui', plugins_url('src/UI/assets/mutqan-ui.css', MUTQAN_FILE), array(), MUTQAN_VERSION);
        wp_enqueue_script('mutqan-ui', plugins_url('src/UI/assets/mutqan-ui.js', MUTQAN_FILE), array(), MUTQAN_VERSION, true);
    }

    public static function role() {
        if (!is_user_logged_in()) return 'visitor';
        $u = wp_get_current_user();
        foreach (array('mutqan_owner','mutqan_site_admin','mutqan_manager','mutqan_supervisor','mutqan_operations','mutqan_technician','mutqan_customer','mutqan_partner') as $r) {
            if (in_array($r, (array) $u->roles, true)) return $r;
        }
        return 'visitor';
    }

    private static function resolve_view($view) {
        $view = sanitize_key($view);
        $allowed = array('home','customer','customer_services','technician','operations','supervisor','manager','owner','site_admin','control');
        if (!in_array($view, $allowed, true)) return self::role_to_view(self::role());

        if (class_exists('MUTQAN_Portal_Guard')) {
            $slug = get_post_field('post_name', get_queried_object_id());
            $expected = MUTQAN_Portal_Guard::expected_view($slug);
            if ($expected && $expected !== $view && !current_user_can('manage_options')) {
                return self::role_to_view(self::role());
            }
        }

        $actual = self::role();
        $role_views = array(
            'mutqan_customer'=>array('customer','customer_services'),
            'mutqan_technician'=>array('technician'),
            'mutqan_operations'=>array('operations'),
            'mutqan_supervisor'=>array('supervisor'),
            'mutqan_manager'=>array('manager'),
            'mutqan_owner'=>array('owner','control'),
            'mutqan_site_admin'=>array('site_admin','control'),
            'mutqan_partner'=>array('home'),
        );
        if (isset($role_views[$actual]) && !in_array($view, $role_views[$actual], true) && !current_user_can('manage_options')) {
            return self::role_to_view($actual);
        }
        return $view;
    }

    private static function role_to_view($role) {
        $map = array('mutqan_customer'=>'customer','mutqan_technician'=>'technician','mutqan_operations'=>'operations','mutqan_supervisor'=>'supervisor','mutqan_manager'=>'manager','mutqan_site_admin'=>'site_admin','mutqan_owner'=>'owner','mutqan_partner'=>'home','visitor'=>'home');
        return isset($map[$role]) ? $map[$role] : 'home';
    }

    public static function render($atts = array()) {
        $atts = shortcode_atts(array('view'=>'auto'), $atts);
        $actual = self::role();
        $view = sanitize_key($atts['view']);
        if ($view === 'auto') $view = self::role_to_view($actual);
        $view = self::resolve_view($view);
        $catalog = self::catalog();
        $meta = self::view_meta($view);
        ob_start(); ?>
        <div class="mq-shell mq-view-<?php echo esc_attr($view); ?>" data-role="<?php echo esc_attr($actual); ?>" data-view="<?php echo esc_attr($view); ?>">
            <div class="mq-noise" aria-hidden="true"></div>
            <aside class="mq-sidebar">
                <div class="mq-sidebar-brand"><img src="<?php echo esc_url(plugins_url('src/UI/assets/mutqan-mark.svg', MUTQAN_FILE)); ?>" alt=""><div><strong>مُتقِن</strong><span><?php echo esc_html($meta['eyebrow']); ?></span></div></div>
                <div class="mq-sidebar-status"><i></i><span>النظام متصل</span><small>البيانات محفوظة في WordPress</small></div>
                <nav class="mq-menu" aria-label="تنقل النظام">
                    <?php foreach ($meta['nav'] as $key=>$label): ?><a href="#<?php echo esc_attr($key); ?>" data-mq-route="<?php echo esc_attr($key); ?>"><span class="mq-nav-icon"><?php echo esc_html(self::nav_icon($key)); ?></span><span><?php echo esc_html($label); ?></span></a><?php endforeach; ?>
                </nav>
                <div class="mq-sidebar-footer"><a href="#settings" data-mq-route="settings"><span>⚙</span> الإعدادات</a><a href="<?php echo esc_url(home_url('/')); ?>"><span>↩</span> الموقع</a></div>
            </aside>

            <main class="mq-main">
                <header class="mq-topbar">
                    <button class="mq-menu-toggle" type="button" data-mq-toggle aria-label="فتح القائمة">☰</button>
                    <div class="mq-breadcrumb"><span><?php echo esc_html($meta['eyebrow']); ?></span><strong><?php echo esc_html($meta['title']); ?></strong></div>
                    <div class="mq-top-actions"><button class="mq-icon-action" type="button" data-mq-theme aria-label="تبديل المظهر">◐</button><div class="mq-user-pill"><span class="mq-avatar"><?php echo esc_html(self::avatar_letter()); ?></span><span><?php echo is_user_logged_in()?esc_html(wp_get_current_user()->display_name):'زائر'; ?></span></div></div>
                </header>

                <div class="mq-content">
                    <section class="mq-page mq-visible" data-mq-page="home"><?php self::hero($meta,$view); ?><?php self::dashboard($view); ?></section>
                    <section class="mq-page" data-mq-page="services"><?php self::services($catalog); ?></section>
                    <section class="mq-page" data-mq-page="orders"><?php self::orders($view); ?></section>
                    <section class="mq-page" data-mq-page="radar">
                        <div class="mq-section-head"><div><span>المتابعة المباشرة</span><h2>الخريطة والرادار</h2></div><b class="mq-live"><i></i> مباشر</b></div>
                        <div class="mq-radar"><div class="mq-radar-grid"></div><div class="mq-radar-ring r1"></div><div class="mq-radar-ring r2"></div><div class="mq-radar-ring r3"></div><div class="mq-radar-center"><span>مُتقِن</span><b>الرادار</b></div><div class="mq-radar-dot d1">ف</div><div class="mq-radar-dot d2">ط</div><div class="mq-radar-dot d3">م</div></div>
                    </section>
                    <section class="mq-page" data-mq-page="offers">
                        <div class="mq-section-head"><div><span>التسويق</span><h2>العروض والخصومات</h2></div></div>
                        <div class="mq-feature-grid"><article class="mq-feature-card mq-feature-gold"><span>✦</span><b>العروض</b><small>إدارة العروض والحملات من نفس النظام.</small></article><article class="mq-feature-card"><span>٪</span><b>الخصومات</b><small>قواعد الخصم والكوبونات ونقاط الولاء.</small></article><article class="mq-feature-card"><span>⌁</span><b>الإشعارات</b><small>قنوات التواصل والتنبيهات متعددة القنوات.</small></article></div>
                    </section>
                    <section class="mq-page" data-mq-page="settings">
                        <div class="mq-section-head"><div><span>النظام</span><h2>الإعدادات والتحكم</h2></div></div>
                        <div class="mq-settings-grid"><article><b>الهوية البصرية</b><span>ألوان وشعار ومظهر موحد لكل الواجهات.</span></article><article><b>الصلاحيات</b><span>الصلاحيات تُحكم من MUTQAN Core وليس من الواجهة.</span></article><article><b>التدقيق</b><span>تسجيل العمليات الحساسة ومراجعتها مركزيًا.</span></article><article><b>الذكاء الاصطناعي</b><span>Gemini يظهر عند إعداد مزود الخدمة والمفتاح.</span></article></div>
                    </section>
                </div>

                <nav class="mq-mobile-nav"><?php foreach ($meta['nav'] as $key=>$label): ?><a href="#<?php echo esc_attr($key); ?>" data-mq-route="<?php echo esc_attr($key); ?>"><span><?php echo esc_html(self::nav_icon($key)); ?></span><small><?php echo esc_html($label); ?></small></a><?php endforeach; ?><a href="#settings" data-mq-route="settings"><span>⚙</span><small>إعدادات</small></a></nav>
            </main>
        </div>
        <?php return ob_get_clean();
    }

    private static function view_meta($view) {
        $map = array(
            'home'=>array('eyebrow'=>'البوابة الرئيسية','title'=>'مُتقِن — مركز الخدمات','nav'=>array('home'=>'الرئيسية','services'=>'الخدمات','offers'=>'العروض')),
            'customer'=>array('eyebrow'=>'بوابة العميل','title'=>'مساحتي في مُتقِن','nav'=>array('home'=>'الرئيسية','services'=>'الخدمات','orders'=>'طلباتي','offers'=>'العروض')),
            'customer_services'=>array('eyebrow'=>'الطلب والخدمات','title'=>'اختر خدمتك','nav'=>array('home'=>'الرئيسية','services'=>'الخدمات','orders'=>'طلباتي')),
            'technician'=>array('eyebrow'=>'بوابة الفني','title'=>'مركز عمل الفني','nav'=>array('home'=>'الرئيسية','orders'=>'مهامي','radar'=>'الخريطة الحية')),
            'operations'=>array('eyebrow'=>'غرفة العمليات','title'=>'مركز التشغيل والتوزيع','nav'=>array('home'=>'الرئيسية','orders'=>'الطلبات','radar'=>'الرادار','services'=>'الخدمات')),
            'supervisor'=>array('eyebrow'=>'الإشراف الميداني','title'=>'لوحة المشرف','nav'=>array('home'=>'الرئيسية','orders'=>'المتابعة','radar'=>'الرادار','services'=>'الخدمات')),
            'manager'=>array('eyebrow'=>'الإدارة','title'=>'لوحة المدير','nav'=>array('home'=>'الرئيسية','orders'=>'التشغيل','services'=>'الخدمات','offers'=>'التسويق')),
            'owner'=>array('eyebrow'=>'المالك','title'=>'مركز التحكم التنفيذي','nav'=>array('home'=>'الرئيسية','orders'=>'الأعمال','radar'=>'المتابعة','services'=>'الخدمات','offers'=>'التسويق')),
            'site_admin'=>array('eyebrow'=>'مشرف الموقع','title'=>'مركز تصميم وتشغيل الواجهات','nav'=>array('home'=>'الرئيسية','services'=>'الواجهات','orders'=>'الوحدات','settings'=>'التحكم')),
            'control'=>array('eyebrow'=>'التحكم المركزي','title'=>'مركز MUTQAN المركزي','nav'=>array('home'=>'الرئيسية','services'=>'الوحدات','orders'=>'السجل','radar'=>'المراقبة','settings'=>'الإعدادات'))
        );
        return isset($map[$view])?$map[$view]:$map['home'];
    }

    private static function hero($meta,$view) {
        $copy=array('home'=>'كل خدمات الصيانة والمتابعة في تجربة واحدة واضحة.','customer'=>'اطلب الخدمة، حدد الموقع، وتابع حالة طلبك من مكان واحد.','customer_services'=>'اختر المجال ثم الخدمة، واترك التشغيل لباقي النظام.','technician'=>'المهام، الحالة، الموقع والتقارير في شاشة عمل واحدة.','operations'=>'وزّع الطلبات وتابع الحركة والحالة التشغيلية لحظة بلحظة.','supervisor'=>'راجع التنفيذ والجودة والضمان قبل إغلاق المهمة.','manager'=>'صورة تشغيلية موحدة للأعمال والفرق والخدمات.','owner'=>'مؤشرات النظام والصلاحيات والأعمال تحت رؤية واحدة.','site_admin'=>'إدارة مكونات الواجهة والوحدات دون كسر النظام الأساسي.','control'=>'نقطة مراقبة واحدة للواجهات والصلاحيات والأحداث.'); ?>
        <section class="mq-hero"><div class="mq-hero-copy"><div class="mq-kicker"><i></i><?php echo esc_html($meta['eyebrow']); ?></div><h1><?php echo esc_html($meta['title']); ?></h1><p><?php echo esc_html(isset($copy[$view])?$copy[$view]:$copy['home']); ?></p><div class="mq-hero-actions"><a href="#services" data-mq-route="services" class="mq-primary-btn">ابدأ من الخدمات <b>←</b></a><a href="#orders" data-mq-route="orders" class="mq-ghost-btn">المتابعة <b>⌁</b></a></div></div><div class="mq-hero-art" aria-hidden="true"><img src="<?php echo esc_url(plugins_url('src/UI/assets/mutqan-hero.svg', MUTQAN_FILE)); ?>" alt=""></div></section>
        <?php
    }

    private static function dashboard($view) {
        $sets=array(
            'home'=>array(array('❄','التكييف والتبريد','صيانة، تنظيف، تركيب وفحص.'),array('⚡','الكهرباء','أعمال وتمديدات ولوحات وتحكم.'),array('🚰','السباكة','كشف تسرب وإصلاح وتركيب.'),array('🛠','الصيانة العامة','نجارة، دهانات وتجهيزات.')),
            'customer'=>array(array('＋','طلب جديد','ابدأ طلب خدمة وحدد الموقع.'),array('◷','طلباتي','تابع الحالات والمواعيد.'),array('✦','العروض','راجع العروض والخصومات.'),array('⌁','المساعدة الذكية','مساعد AI للخدمة والاستفسار.')),
            'customer_services'=>array(array('❄','تكييف وتبريد','المكيفات المركزية والسبلت والدكت والتبريد.'),array('🧊','غرف التبريد','فحص وصيانة غرف وثلاجات التبريد.'),array('🧽','تنظيف وتعقيم','تنظيف، تعقيم وخزانات ومكافحة آفات.'),array('🚚','نقل وتجهيز','نقل أثاث، تغليف وتخزين ومعدات.')),
            'technician'=>array(array('◷','مهام اليوم','المهام المرتبطة بك وحالتها الحالية.'),array('⌖','الموقع','الخريطة والحركة والوجهة.'),array('✓','التقارير','تنفيذ، صور، ملاحظات وتوقيع.'),array('◌','المساعد الذكي','مساعدة في الجدول والطلبات والمشكلات.')),
            'operations'=>array(array('▣','الطلبات','استقبال وتوزيع وإعادة إسناد.'),array('⌖','الرادار','GPS والحركة والحالة الميدانية.'),array('◉','الفنيون','التوفر والطاقة والمهام.'),array('▤','المخزون والأسطول','قطع الغيار والمركبات والمعدات.')),
            'supervisor'=>array(array('✓','مراجعة التنفيذ','قوائم الفحص والتأكد من الإغلاق.'),array('★','الجودة والضمان','متابعة الجودة والضمان وخدمة ما بعد البيع.'),array('⌁','المشكلات','التصعيد والمتابعة مع العمليات.'),array('▤','التقارير','تقارير الفريق والأداء.')),
            'manager'=>array(array('◈','الأعمال','نظرة تشغيلية على الطلبات والخدمات.'),array('ر','المالية','الفواتير والتحصيل والمصروفات.'),array('♧','الفريق','الموارد والقدرة التشغيلية.'),array('✦','التسويق','العروض والحملات والولاء.')),
            'owner'=>array(array('◆','الرؤية التنفيذية','ملخص النظام والوحدات والعمليات.'),array('◈','المالية','مؤشرات المبيعات والفواتير والتسويات.'),array('⌖','المتابعة','الرادار والتشغيل وجودة الخدمة.'),array('⚙','التحكم','الصلاحيات والإعدادات والتدقيق.')),
            'site_admin'=>array(array('▦','الواجهات','القوالب ومكونات صفحات الأنظمة.'),array('◇','الوحدات','ربط الواجهة بالوحدات الأساسية.'),array('↔','الربط','الأحداث والتنبيهات وتدفق البيانات.'),array('⚙','الصلاحيات','تحكم مركزي حسب capability.')),
            'control'=>array(array('◎','السجل','الأحداث والتغييرات الحساسة.'),array('◇','الوحدات','Registry والوحدات والإصدارات.'),array('⌁','المراقبة','جاهزية التكاملات والتنبيهات.'),array('⚙','الإعدادات','الهوية، AI، الصلاحيات والإعدادات.'))
        );
        $cards=isset($sets[$view])?$sets[$view]:$sets['home']; ?>
        <div class="mq-stat-strip"><div><b>01</b><span>نظام موحد</span></div><div><b>06</b><span>وحدات أساسية</span></div><div><b>∞</b><span>قابل للتوسع</span></div><div><b>24/7</b><span>متابعة</span></div></div>
        <div class="mq-section-head mq-section-head-tight"><div><span>الوصول السريع</span><h2>مكونات <?php echo esc_html($view==='home'?'النظام':'البوابة'); ?></h2></div><span class="mq-section-note">نفس البيانات — واجهة جديدة</span></div>
        <div class="mq-card-grid mq-card-grid-rich"><?php foreach($cards as $i=>$card): ?><button class="mq-card mq-card-rich" type="button" data-mq-card="<?php echo esc_attr($i); ?>"><span class="mq-card-icon"><?php echo esc_html($card[0]); ?></span><strong><?php echo esc_html($card[1]); ?></strong><small><?php echo esc_html($card[2]); ?></small><em>فتح <b>←</b></em></button><?php endforeach; ?></div>
        <div class="mq-bottom-panels"><article class="mq-panel"><div class="mq-panel-head"><div><span>الحالة</span><b>جاهزية النظام</b></div><i class="mq-check">✓</i></div><div class="mq-progress"><span style="width:86%"></span></div><small>الواجهة الجديدة تعمل فوق MUTQAN Core مع بقاء البيانات والصلاحيات في WordPress.</small></article><article class="mq-panel mq-panel-dark"><div><span class="mq-panel-label">AI / Gemini</span><b>مساعد مُتقِن</b><p>المساعد موجود في النظام ويظهر عندما تكون إعدادات مزود الذكاء الاصطناعي مكتملة.</p></div><span class="mq-ai-orb">✦</span></article></div>
        <?php
    }

    private static function services($catalog) { ?>
        <div class="mq-section-head"><div><span>كتالوج الخدمات</span><h2>كل المجالات في مكان واحد</h2></div><span class="mq-section-note"><?php echo esc_html(count($catalog)); ?> مجالات</span></div>
        <div class="mq-service-tree"><?php foreach($catalog as $group): ?><article class="mq-service-group"><button class="mq-group-head" type="button" data-mq-expand><span class="mq-service-title"><i><?php echo esc_html($group['icon']); ?></i><b><?php echo esc_html($group['name']); ?></b></span><span class="mq-group-count"><?php echo esc_html(count($group['items'])); ?> خدمات <strong>＋</strong></span></button><div class="mq-subservices"><?php foreach($group['items'] as $item): ?><button class="mq-subservice" type="button"><span><?php echo esc_html($item['name']); ?></span><b>←</b></button><?php endforeach; ?></div></article><?php endforeach; ?></div>
        <?php
    }

    private static function orders($view) { $label=$view==='technician'?'المهام':'الطلبات'; ?>
        <div class="mq-section-head"><div><span>التشغيل</span><h2><?php echo esc_html($label); ?></h2></div><b class="mq-filter">آخر النشاطات ▾</b></div>
        <div class="mq-order-layout"><article class="mq-panel"><div class="mq-empty-illustration">⌁</div><b>مساحة العمل مرتبطة بالنظام</b><p>هذه الواجهة تغيّر طريقة العرض فقط؛ إنشاء الطلبات وتوزيعها وحالاتها والتقارير تبقى من MUTQAN Core.</p><a href="#services" data-mq-route="services" class="mq-primary-btn small">فتح الخدمات</a></article><aside class="mq-timeline"><div><i></i><span><b>الطلبات</b><small>بيانات العمليات من قاعدة النظام.</small></span></div><div><i></i><span><b>التوزيع</b><small>Dispatch وإعادة الإسناد.</small></span></div><div><i></i><span><b>التنفيذ</b><small>Field execution والتقارير.</small></span></div><div><i></i><span><b>الإغلاق</b><small>الجودة والضمان والتدقيق.</small></span></div></aside></div>
        <?php
    }

    private static function nav_icon($key) {
        $icons=array('home'=>'⌂','services'=>'◈','orders'=>'▤','radar'=>'⌖','offers'=>'✦','settings'=>'⚙');
        return isset($icons[$key])?$icons[$key]:'•';
    }

    private static function avatar_letter() {
        if (!is_user_logged_in()) return 'ز';
        $name=trim((string)wp_get_current_user()->display_name);
        return $name!==''?mb_substr($name,0,1,'UTF-8'):'م';
    }

    public static function catalog() { return get_option(self::CATALOG_OPTION,self::catalog_defaults()); }

    private static function catalog_defaults() {
        return array(
            array('id'=>'ac','icon'=>'❄️','name'=>'التكييف والتبريد','items'=>array(array('id'=>'ac-maint','name'=>'صيانة'),array('id'=>'ac-clean','name'=>'غسيل وتنظيف'),array('id'=>'ac-install','name'=>'تركيب'),array('id'=>'ac-inspect','name'=>'فحص وتشخيص'),array('id'=>'ac-survey','name'=>'معاينة'))),
            array('id'=>'fridge','icon'=>'🧊','name'=>'الثلاجات والتبريد','items'=>array(array('id'=>'fr-maint','name'=>'صيانة'),array('id'=>'fr-clean','name'=>'تنظيف'),array('id'=>'fr-install','name'=>'تركيب'),array('id'=>'fr-inspect','name'=>'فحص ومعاينة'))),
            array('id'=>'electric','icon'=>'⚡','name'=>'الكهرباء','items'=>array(array('id'=>'el-maint','name'=>'صيانة'),array('id'=>'el-install','name'=>'تركيب'),array('id'=>'el-inspect','name'=>'فحص أعطال'),array('id'=>'el-panels','name'=>'لوحات وتحكم'))),
            array('id'=>'plumbing','icon'=>'🚰','name'=>'السباكة','items'=>array(array('id'=>'pl-maint','name'=>'صيانة وإصلاح'),array('id'=>'pl-install','name'=>'تركيب'),array('id'=>'pl-leak','name'=>'كشف تسرب'))),
            array('id'=>'general','icon'=>'🛠️','name'=>'الصيانة العامة','items'=>array(array('id'=>'gen-carpentry','name'=>'نجارة'),array('id'=>'gen-paint','name'=>'دهانات'),array('id'=>'gen-metal','name'=>'حدادة وأعمال معدنية'),array('id'=>'gen-building','name'=>'تجهيزات عامة'))),
            array('id'=>'cleaning','icon'=>'🧽','name'=>'التنظيف والتعقيم','items'=>array(array('id'=>'cl-home','name'=>'منازل'),array('id'=>'cl-commercial','name'=>'منشآت'),array('id'=>'cl-tanks','name'=>'خزانات'),array('id'=>'cl-pest','name'=>'مكافحة آفات'))),
            array('id'=>'moving','icon'=>'🚚','name'=>'النقل والتجهيز','items'=>array(array('id'=>'mv-furniture','name'=>'نقل أثاث'),array('id'=>'mv-packing','name'=>'تغليف وتخزين'),array('id'=>'mv-equipment','name'=>'نقل معدات')))
        );
    }

    public static function admin_menu() {
        add_submenu_page('mutqan','واجهات الأنظمة','واجهات الأنظمة','mutqan_manage_settings','mutqan-interfaces',array(__CLASS__,'interfaces_page'));
    }

    public static function interfaces_page() {
        if (!current_user_can('mutqan_manage_settings')) wp_die('Unauthorized');
        echo '<div class="wrap" dir="rtl"><h1>مُتقِن — صفحات الأنظمة</h1><p>الواجهات مستقلة بصريًا وتعمل فوق نفس النواة والبيانات.</p></div>';
    }

    public static function create_pages() {
        if (!current_user_can('mutqan_manage_settings')) wp_die('Unauthorized');
        check_admin_referer('mutqan_create_pages');
        foreach(array('customer'=>'العميل','technician'=>'الفني','operations'=>'العمليات','site_admin'=>'مشرف الموقع','owner'=>'المالك') as $v=>$l) {
            $slug='mutqan-'.$v;
            if (get_page_by_path($slug)) continue;
            wp_insert_post(array('post_title'=>'مُتقِن — '.$l,'post_name'=>$slug,'post_content'=>'[mutqan_app view="'.$v.'"]','post_status'=>'publish','post_type'=>'page'));
        }
        wp_safe_redirect(admin_url('admin.php?page=mutqan-interfaces&created=1'));
        exit;
    }

    public static function rest() {
        register_rest_route('mutqan/v1','/ui-config',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function(){
            return rest_ensure_response(array('role'=>self::role(),'catalog'=>self::catalog()));
        }));
    }
}

MUTQAN_App::init();

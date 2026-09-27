<?php
defined('ABSPATH') || exit;

final class MUTQAN_Stitch_UI {
    const VERSION = '1.0.1';

    public static function init() {
        add_filter('template_include', array(__CLASS__, 'template'), 99);
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'), 99);
    }

    private static function map() {
        return array(
            'mutqan-control' => 'overview',
            'mutqan-operations' => 'operations',
            'mutqan-supervisor' => 'technicians',
            'mutqan-manager' => 'inventory',
            'mutqan-owner' => 'item',
            'mutqan-site-supervisor' => 'fleet',
        );
    }

    public static function template($template) {
        if (!is_page()) return $template;
        $slug = get_post_field('post_name', get_queried_object_id());
        $map = self::map();
        if (!isset($map[$slug])) return $template;
        $GLOBALS['mutqan_stitch_view'] = $map[$slug];
        return MUTQAN_DIR . 'src/UI/templates/stitch-app.php';
    }

    public static function assets() {
        if (!is_page()) return;
        $slug = get_post_field('post_name', get_queried_object_id());
        if (!isset(self::map()[$slug])) return;
        wp_enqueue_style('mutqan-stitch-ui', plugins_url('src/UI/assets/mutqan-stitch.css', MUTQAN_FILE), array(), self::VERSION);
        wp_enqueue_script('mutqan-stitch-ui', plugins_url('src/UI/assets/mutqan-stitch.js', MUTQAN_FILE), array(), self::VERSION, true);
        wp_localize_script('mutqan-stitch-ui', 'MUTQAN_STITCH', array(
            'root' => esc_url_raw(rest_url('mutqan/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }

    private static function table($class, $fallback) {
        return class_exists($class) && method_exists($class, 'table') ? call_user_func(array($class, 'table')) : $fallback;
    }

    private static function counts() {
        global $wpdb;
        $ops=self::table('MUTQAN_Operations',$wpdb->prefix.'mutqan_operations');
        $inv=self::table('MUTQAN_Inventory',$wpdb->prefix.'mutqan_inventory');
        $fleet=self::table('MUTQAN_Fleet',$wpdb->prefix.'mutqan_fleet');
        $services=self::table('MUTQAN_Services',$wpdb->prefix.'mutqan_services');
        return array(
            'orders'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$ops}"),
            'active_orders'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$ops} WHERE status NOT IN ('closed','cancelled')"),
            'inventory'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$inv} WHERE active=1"),
            'fleet'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$fleet}"),
            'services'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$services} WHERE status='active'"),
            'customers'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} m ON m.user_id=u.ID AND m.meta_key='{$wpdb->prefix}capabilities' WHERE m.meta_value LIKE '%mutqan_customer%'"),
            'technicians'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} m ON m.user_id=u.ID AND m.meta_key='{$wpdb->prefix}capabilities' WHERE m.meta_value LIKE '%mutqan_technician%'"),
        );
    }

    private static function view() {
        return isset($GLOBALS['mutqan_stitch_view']) ? sanitize_key($GLOBALS['mutqan_stitch_view']) : 'overview';
    }

    private static function nav() {
        return array(
            'overview'=>array('mutqan-control','نظرة عامة والعمليات المباشرة','⌂'),
            'operations'=>array('mutqan-operations','غرفة العمليات والمهام','☷'),
            'technicians'=>array('mutqan-supervisor','شبكة الفنيين ومزودي الخدمة','♙'),
            'inventory'=>array('mutqan-manager','المستودع وقطع الغيار','▦'),
            'item'=>array('mutqan-owner','تفاصيل الصنف والتتبع','▣'),
            'fleet'=>array('mutqan-site-supervisor','عُهد السيارات والمخزون الميداني','▰'),
        );
    }

    private static function rows($view) {
        global $wpdb;
        $ops=self::table('MUTQAN_Operations',$wpdb->prefix.'mutqan_operations');
        $inv=self::table('MUTQAN_Inventory',$wpdb->prefix.'mutqan_inventory');
        $fleet=self::table('MUTQAN_Fleet',$wpdb->prefix.'mutqan_fleet');
        if ($view==='operations'||$view==='overview') {
            $rows=$wpdb->get_results("SELECT * FROM {$ops} ORDER BY id DESC LIMIT 50",ARRAY_A);
            foreach($rows as &$r) $r['payload']=$r['payload']?json_decode($r['payload'],true):array();
            return $rows;
        }
        if ($view==='inventory') return $wpdb->get_results("SELECT * FROM {$inv} WHERE active=1 ORDER BY id DESC LIMIT 50",ARRAY_A);
        if ($view==='fleet') return $wpdb->get_results("SELECT * FROM {$fleet} ORDER BY id DESC LIMIT 50",ARRAY_A);
        if ($view==='technicians') return get_users(array('role'=>'mutqan_technician','fields'=>array('ID','display_name','user_email'),'number'=>100));
        return array();
    }

    private static function status($s) {
        $m=array('new'=>'جديد','pending_assignment'=>'بانتظار الإسناد','assigned'=>'مسند','accepted'=>'مقبول','declined'=>'مرفوض','en_route'=>'في الطريق','nearby'=>'بالقرب','arrived'=>'وصل','working'=>'قيد التنفيذ','waiting_customer'=>'بانتظار العميل','completed'=>'مكتمل','cancelled'=>'ملغى','closed'=>'مغلق');
        return isset($m[$s])?$m[$s]:($s?:'غير محدد');
    }

    private static function page_url($slug) {
        $p=get_page_by_path($slug);
        return $p?get_permalink($p):home_url('/'.$slug.'/');
    }

    private static function shell_header($title,$subtitle) {
        ?><header class="mq-head"><div><span class="mq-eyebrow">مُتقِن / لوحة التشغيل</span><h1><?php echo esc_html($title); ?></h1><p><?php echo esc_html($subtitle); ?></p></div><div class="mq-head-actions"><span class="mq-ready">● النظام جاهز للتشغيل</span><a href="<?php echo esc_url(admin_url()); ?>">إدارة WordPress</a></div></header><?php
    }

    private static function kpi($label,$value,$note) {
        ?><article class="mq-kpi"><span><?php echo esc_html($label); ?></span><strong><?php echo esc_html(number_format_i18n((int)$value)); ?></strong><small><?php echo esc_html($note); ?></small></article><?php
    }

    private static function empty($title,$text,$href='') {
        ?><div class="mq-empty"><div class="mq-empty-icon">✓</div><h3><?php echo esc_html($title); ?></h3><p><?php echo esc_html($text); ?></p><?php if($href): ?><a class="mq-primary" href="<?php echo esc_url($href); ?>">فتح الإدارة</a><?php endif; ?></div><?php
    }

    private static function table_search($placeholder) {
        ?><div class="mq-toolbar"><label>⌕ <input type="search" data-mq-filter placeholder="<?php echo esc_attr($placeholder); ?>"></label><button type="button" data-mq-refresh>↻ تحديث</button></div><?php
    }

    private static function orders($rows) {
        if(!$rows){self::empty('لا توجد طلبات تشغيلية','قاعدة الطلبات فارغة. عند إنشاء أول طلب سيظهر هنا مباشرة.');return;}
        ?><div class="mq-table-wrap"><table><thead><tr><th>الطلب</th><th>العميل والموقع</th><th>الخدمة</th><th>الفني</th><th>الحالة</th><th>التاريخ</th></tr></thead><tbody><?php
        foreach($rows as $r){$p=is_array($r['payload']??null)?$r['payload']:array();$c=$r['customer_id']?get_userdata((int)$r['customer_id']):null;$t=$r['technician_id']?get_userdata((int)$r['technician_id']):null;?>
        <tr data-mq-row><td><b>#<?php echo (int)$r['id']; ?></b><small><?php echo esc_html($r['priority']); ?></small></td><td><?php echo esc_html($c?$c->display_name:'—'); ?><small><?php echo esc_html($p['location_label']??'موقع غير محدد'); ?></small></td><td><?php echo esc_html($p['service_name']??$p['service']??'طلب صيانة'); ?></td><td><?php echo esc_html($t?$t->display_name:'غير مسند'); ?></td><td><span class="mq-status"><?php echo esc_html(self::status($r['status'])); ?></span></td><td><?php echo esc_html(mysql2date('Y-m-d H:i',$r['created_at'])); ?></td></tr>
        <?php } ?></tbody></table></div><?php
    }

    private static function render_view($view,$rows,$c) {
        if($view==='overview'){
            self::table_search('بحث في الطلبات والعملاء والفنيين');
            ?><div class="mq-grid-2"><section class="mq-panel"><div class="mq-panel-title"><h2>الخريطة والانتشار الميداني</h2><span><?php echo esc_html($c['active_orders']); ?> نشط</span></div><div class="mq-map"><b>لا توجد نقاط ميدانية بعد</b><small>ستظهر المواقع الحقيقية بعد إنشاء الطلبات وتسجيل المركبات.</small></div></section><section class="mq-panel"><div class="mq-panel-title"><h2>البث اللحظي لحركة الفرق</h2><span>Live</span></div><div class="mq-event-empty">سجل الأحداث التشغيلية سيظهر هنا عند بدء التشغيل.</div></section></div><section class="mq-panel"><div class="mq-panel-title"><h2>جدول إدارة وتوجيه المهام</h2><span><?php echo esc_html($c['orders']); ?> طلب</span></div><?php self::orders($rows); ?></section><?php
            return;
        }
        if($view==='operations'){
            self::table_search('رقم الطلب أو الخدمة أو الموقع');
            ?><section class="mq-panel"><div class="mq-panel-title"><h2>غرفة العمليات والمهام الميدانية</h2><span>بيانات MUTQAN الفعلية</span></div><?php self::orders($rows); ?></section><?php
            return;
        }
        if($view==='technicians'){
            self::table_search('اسم الفني أو البريد');
            ?><section class="mq-panel"><div class="mq-panel-title"><h2>شبكة الفنيين ومزودي الخدمة</h2><span><?php echo count($rows); ?> فني</span></div><?php if(!$rows){self::empty('لا يوجد فنيون مسجلون','الحسابات نظيفة. عند التسجيل والاعتماد سيظهر الفني هنا.');}else{?><div class="mq-table-wrap"><table><thead><tr><th>الفني</th><th>البريد</th><th>الحساب</th><th>الإدارة</th></tr></thead><tbody><?php foreach($rows as $u): ?><tr data-mq-row><td><b><?php echo esc_html($u->display_name); ?></b></td><td><?php echo esc_html($u->user_email); ?></td><td><span class="mq-status good">مسجل</span></td><td><a href="<?php echo esc_url(get_edit_user_link($u->ID)); ?>">تعديل</a></td></tr><?php endforeach; ?></tbody></table></div><?php } ?></section><?php
            return;
        }
        if($view==='inventory'){
            self::table_search('SKU أو اسم الصنف أو المستودع');
            ?><section class="mq-panel"><div class="mq-panel-title"><h2>المستودع وقطع الغيار</h2><span><?php echo count($rows); ?> سجل</span></div><?php if(!$rows){self::empty('المستودع فارغ','لا توجد أصناف تجريبية. أضف أول صنف من إدارة المستودع.');}else{?><div class="mq-table-wrap"><table><thead><tr><th>الصنف / SKU</th><th>المستودع</th><th>المتاح</th><th>المحجوز</th><th>التكلفة</th><th>حد إعادة الطلب</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr data-mq-row><td><b><?php echo esc_html($r['name']); ?></b><small><?php echo esc_html($r['sku']); ?></small></td><td><?php echo esc_html($r['warehouse']); ?></td><td><?php echo esc_html($r['quantity']); ?></td><td><?php echo esc_html($r['reserved']); ?></td><td><?php echo esc_html(number_format((float)$r['unit_cost'],2)); ?> ر.س</td><td><?php echo esc_html($r['reorder_level']); ?></td></tr><?php endforeach; ?></tbody></table></div><?php } ?></section><?php
            return;
        }
        if($view==='item'){
            global $wpdb;$id=absint($_GET['item']??0);$inv=self::table('MUTQAN_Inventory',$wpdb->prefix.'mutqan_inventory');$r=$id?$wpdb->get_row($wpdb->prepare("SELECT * FROM {$inv} WHERE id=%d",$id),ARRAY_A):null;
            ?><section class="mq-item"><div class="mq-panel"><div class="mq-panel-title"><div><span class="mq-eyebrow">المستودع / التتبع</span><h2><?php echo esc_html($r?$r['name']:'تفاصيل الصنف'); ?></h2></div><button type="button">طباعة QR</button></div><?php if(!$r)self::empty('لم يتم اختيار صنف','اختر صنفًا من شاشة المستودع لعرض تفاصيله.');else{?><div class="mq-detail-grid"><?php foreach(array('sku'=>'SKU','warehouse'=>'المستودع','quantity'=>'الكمية','reserved'=>'المحجوز','unit_cost'=>'تكلفة الوحدة','reorder_level'=>'حد إعادة الطلب') as $k=>$label): ?><div><span><?php echo esc_html($label); ?></span><b><?php echo esc_html($r[$k]); ?><?php echo $k==='unit_cost'?' ر.س':''; ?></b></div><?php endforeach; ?></div><?php } ?></div><aside class="mq-panel"><h2>جواز التتبع الرقمي</h2><p>جاهز لربط QR والباركود وسجل التركيب والضمان بالبيانات الفعلية.</p><div class="mq-qr">QR</div></aside></section><?php
            return;
        }
        if($view==='fleet'){
            self::table_search('كود المركبة أو اللوحة أو الفني');
            ?><section class="mq-panel"><div class="mq-panel-title"><h2>عُهد السيارات والمخزون الميداني</h2><span><?php echo count($rows); ?> مركبة</span></div><?php if(!$rows)self::empty('لا توجد مركبات مسجلة','تم تنظيف بيانات الأسطول. أضف أول مركبة عند بدء التشغيل.');else{?><div class="mq-table-wrap"><table><thead><tr><th>المركبة</th><th>اللوحة</th><th>الفني</th><th>الحالة</th><th>السعة</th><th>ملاحظات</th></tr></thead><tbody><?php foreach($rows as $r){$u=$r['technician_id']?get_userdata((int)$r['technician_id']):null;?><tr data-mq-row><td><b><?php echo esc_html($r['name']); ?></b><small><?php echo esc_html($r['vehicle_code']); ?></small></td><td><?php echo esc_html($r['plate']); ?></td><td><?php echo esc_html($u?$u->display_name:'غير مسند'); ?></td><td><span class="mq-status"><?php echo esc_html($r['status']); ?></span></td><td><?php echo esc_html($r['capacity']); ?></td><td><?php echo esc_html($r['notes']); ?></td></tr><?php } ?></tbody></table></div><?php } ?></section><?php
        }
    }

    public static function render() {
        $view=self::view();$c=self::counts();$rows=self::rows($view);
        $titles=array(
            'overview'=>array('مركز العمليات والمراقبة الميدانية','نظرة تشغيلية موحدة مبنية على بيانات MUTQAN الحقيقية.'),
            'operations'=>array('غرفة عمليات الصيانة والمهام الميدانية','إدارة الطلبات والتوجيه والحالات من مصدر بيانات واحد.'),
            'technicians'=>array('إدارة شبكة الفنيين ومزودي الخدمة','التسجيل والاعتماد والأداء دون بيانات تجريبية.'),
            'inventory'=>array('المستودع وقطع الغيار المركزية','الكميات والتكلفة والحالة مرتبطة بجدول المخزون.'),
            'item'=>array('تفاصيل الصنف وبيانات التتبع','بطاقة رقمية قابلة للربط بالـQR والضمان وسجل الاستخدام.'),
            'fleet'=>array('إدارة عُهد سيارات الصيانة','المركبات والعهد والمخزون الميداني من قاعدة واحدة.')
        );
        $nav=self::nav();
        ?><div class="mq-stitch" dir="rtl" data-view="<?php echo esc_attr($view); ?>"><aside class="mq-side"><div class="mq-brand"><strong>مُتقِن</strong><span>أكسجين للصيانة والمقاولات</span></div><div class="mq-live">● النظام جاهز — البيانات التشغيلية نظيفة</div><nav><?php foreach($nav as $key=>$item):$active=$key===$view?' active':'';?><a class="mq-nav<?php echo $active;?>" href="<?php echo esc_url(self::page_url($item[0])); ?>"><i><?php echo esc_html($item[2]);?></i><?php echo esc_html($item[1]);?></a><?php endforeach;?></nav><div class="mq-side-foot"><a href="<?php echo esc_url(admin_url());?>">إدارة WordPress</a><a href="<?php echo esc_url(home_url('/'));?>">الموقع</a></div></aside><main class="mq-main"><?php self::shell_header($titles[$view][0],$titles[$view][1]);?><section class="mq-kpis"><?php self::kpi('الطلبات',$c['orders'],'إجمالي الطلبات');self::kpi('الطلبات النشطة',$c['active_orders'],'غير مغلقة');self::kpi('الفنيون',$c['technicians'],'الحسابات المعتمدة/المسجلة');self::kpi('العملاء',$c['customers'],'حسابات العملاء');self::kpi('الخدمات',$c['services'],'الخدمات النشطة');self::kpi('المخزون',$c['inventory'],'الأصناف النشطة'); ?></section><?php self::render_view($view,$rows,$c);?></main></div><?php
    }
}
MUTQAN_Stitch_UI::init();

<?php
defined('ABSPATH') || exit;

final class MUTQAN_Content_SEO {
    const TABLE = 'mutqan_content_queue';
    const CRON = 'mutqan_content_daily';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'), 45);
        add_action('admin_init', array(__CLASS__, 'settings'));
        add_action('rest_api_init', array(__CLASS__, 'rest'));
        add_action(self::CRON, array(__CLASS__, 'run_queue'));
        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
    }

    public static function install() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            specialty varchar(120) NOT NULL,
            keyword varchar(255) NOT NULL,
            title varchar(255) NOT NULL,
            article_type varchar(80) NOT NULL DEFAULT 'guide',
            status varchar(30) NOT NULL DEFAULT 'draft',
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            quality_score decimal(5,2) NOT NULL DEFAULT 0,
            ai_generated tinyint(1) NOT NULL DEFAULT 0,
            source varchar(40) NOT NULL DEFAULT 'manual',
            service_id bigint(20) unsigned NOT NULL DEFAULT 0,
            scheduled_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY scheduled_at (scheduled_at),
            KEY keyword (keyword(191))
        ) $charset;");
        if (!wp_next_scheduled(self::CRON)) {
            wp_schedule_event(time() + 300, 'daily', self::CRON);
        }
    }

    public static function cron_schedules($schedules) {
        $schedules['mutqan_hourly'] = array('interval' => HOUR_IN_SECONDS, 'display' => 'MUTQAN hourly');
        return $schedules;
    }

    private static function can() {
        return current_user_can('mutqan_manage_content') || current_user_can('manage_options');
    }

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    public static function settings() {
        register_setting('mutqan_content', 'mutqan_content_daily_limit', array('sanitize_callback' => 'absint'));
        register_setting('mutqan_content', 'mutqan_content_auto_publish', array('sanitize_callback' => function($v){ return $v ? 1 : 0; }));
        register_setting('mutqan_content', 'mutqan_content_min_quality', array('sanitize_callback' => function($v){ $v=(float)$v; return max(0,min(100,$v)); }));
        register_setting('mutqan_content', 'mutqan_content_target_site', array('sanitize_callback' => 'esc_url_raw'));
        register_setting('mutqan_content', 'mutqan_gsc_property', array('sanitize_callback' => 'sanitize_text_field'));
        add_settings_section('mutqan_content_main', 'مركز المحتوى و SEO', function(){
            echo '<p>المحتوى يُنشأ بهدف خدمة الزائر أولاً. يمكن توليد المسودات آلياً، ولا يُسمح للنظام بتحويل عدد المقالات إلى هدف جودة بحد ذاته.</p>';
        }, 'mutqan_content');
        add_settings_field('mutqan_content_daily_limit','الحد اليومي',function(){
            echo '<input type="number" min="1" max="10" name="mutqan_content_daily_limit" value="'.esc_attr(get_option('mutqan_content_daily_limit',10)).'">';
        },'mutqan_content','mutqan_content_main');
        add_settings_field('mutqan_content_auto_publish','النشر الآلي بعد الجودة',function(){
            echo '<label><input type="checkbox" name="mutqan_content_auto_publish" value="1" '.checked(get_option('mutqan_content_auto_publish',0),1,false).'> السماح بالنشر بعد اجتياز بوابة الجودة</label>';
        },'mutqan_content','mutqan_content_main');
        add_settings_field('mutqan_content_min_quality','الحد الأدنى للجودة',function(){
            echo '<input type="number" min="60" max="100" name="mutqan_content_min_quality" value="'.esc_attr(get_option('mutqan_content_min_quality',80)).'"> / 100';
        },'mutqan_content','mutqan_content_main');
        add_settings_field('mutqan_content_target_site','واجهة النشر الخارجية الاختيارية',function(){
            echo '<input type="url" class="regular-text" name="mutqan_content_target_site" value="'.esc_attr(get_option('mutqan_content_target_site','')).'" placeholder="اتركها فارغة للنشر على هذا الموقع">';
        },'mutqan_content','mutqan_content_main');
        add_settings_field('mutqan_gsc_property','Search Console Property',function(){
            echo '<input class="regular-text" name="mutqan_gsc_property" value="'.esc_attr(get_option('mutqan_gsc_property','')).'" placeholder="sc-domain:example.com"><p class="description">تسجيل الملكية هنا يجهز الربط؛ OAuth/Service Account لا يُحفظ داخل GitHub.</p>';
        },'mutqan_content','mutqan_content_main');
    }

    public static function menu() {
        if (!self::can()) return;
        add_submenu_page('mutqan','مركز المحتوى و SEO','المحتوى و SEO','mutqan_manage_content','mutqan-content',array(__CLASS__,'page'));
    }

    private static function specialties() {
        return array('المكيفات','الكهرباء','غرف التبريد والتجميد','الصيانة العامة','السباكة','النجارة','الدهانات','المقاولات العامة','المشاريع التنفيذية','وظائف الفنيين','الشركات الصغيرة','الخدمات العامة');
    }

    private static function article_types() {
        return array('guide'=>'دليل عملي','howto'=>'طريقة تنفيذ','faq'=>'أسئلة شائعة','service'=>'شرح خدمة','maintenance'=>'دليل صيانة','safety'=>'السلامة','case-study'=>'دراسة حالة','jobs'=>'وظائف وفرص');
    }

    private static function quality($title,$content,$keyword) {
        $text = wp_strip_all_tags($content);
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $score = 0;
        if (count($words) >= 450) $score += 25;
        elseif (count($words) >= 250) $score += 15;
        if (mb_strlen($title) >= 25 && mb_strlen($title) <= 90) $score += 15;
        if ($keyword !== '' && mb_stripos($text,$keyword) !== false) $score += 15;
        if (substr_count($text,'##') > 0 || substr_count($text,'<h2') > 0) $score += 10;
        if (mb_stripos($text,'مُتقِن') !== false || mb_stripos($text,'MUTQAN') !== false) $score += 5;
        if (preg_match('/(.)\1{7,}/u',$text)) $score -= 15;
        if (substr_count($text,$keyword) > 0 && substr_count($text,$keyword) > 12) $score -= 20;
        if (preg_match('/https?:\/\//',$text)) $score += 5;
        return max(0,min(100,$score + 25));
    }

    private static function generate($specialty,$keyword,$title,$type) {
        $key = defined('MUTQAN_GEMINI_API_KEY') ? MUTQAN_GEMINI_API_KEY : get_option('mutqan_gemini_api_key','');
        if (!$key) return new WP_Error('ai_not_configured','Gemini AI غير مُعد بعد.',array('status'=>503));
        $model = sanitize_text_field(get_option('mutqan_gemini_model','gemini-2.5-flash'));
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent?key='.rawurlencode($key);
        $prompt = "اكتب مسودة عربية أصلية ومفيدة للزائر عن التخصص: {$specialty}. الكلمة/الموضوع: {$keyword}. العنوان: {$title}. النوع: {$type}. الموقع المستهدف إن كان مناسباً: السعودية. لا تحشو الكلمات المفتاحية، ولا تنسخ محتوى من مواقع أخرى، ولا تصنع وعوداً بالترتيب في Google. استخدم عناوين فرعية واضحة، خطوات عملية، أسئلة شائعة عند الحاجة، ومعلومات تساعد العميل فعلياً. أعد HTML صالحاً للمحتوى فقط، بدون <script>.";
        $res = wp_remote_post($url,array('timeout'=>45,'headers'=>array('Content-Type'=>'application/json'),'body'=>wp_json_encode(array(
            'systemInstruction'=>array('parts'=>array(array('text'=>'أنت محرر محتوى مهني لموقع خدمات وصيانة. الأولوية للفائدة والدقة والأصالة وسهولة القراءة.'))),
            'contents'=>array(array('role'=>'user','parts'=>array(array('text'=>$prompt))))
        )));
        if (is_wp_error($res)) return new WP_Error('ai_request_failed','تعذر الاتصال بمزود الذكاء الاصطناعي.',array('status'=>502));
        $code = wp_remote_retrieve_response_code($res);
        $body = json_decode(wp_remote_retrieve_body($res),true);
        if ($code < 200 || $code >= 300) return new WP_Error('ai_provider_error','تعذر تنفيذ طلب Gemini.',array('status'=>502));
        $html = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (!$html) return new WP_Error('empty_ai','لم يصل محتوى صالح.');
        return wp_kses_post($html);
    }

    public static function enqueue($specialty,$keyword,$title,$type='guide',$source='manual') {
        global $wpdb;
        if (!$specialty || !$keyword || !$title) return new WP_Error('invalid_content','التخصص والكلمة والعنوان مطلوبة.');
        $duplicate = $wpdb->get_var($wpdb->prepare("SELECT id FROM ".self::table()." WHERE keyword=%s AND title=%s AND status NOT IN ('rejected') LIMIT 1",$keyword,$title));
        if ($duplicate) return new WP_Error('duplicate_content','يوجد محتوى بنفس العنوان والكلمة.');
        $now=current_time('mysql');
        $wpdb->insert(self::table(),array('specialty'=>sanitize_text_field($specialty),'keyword'=>sanitize_text_field($keyword),'title'=>sanitize_text_field($title),'article_type'=>sanitize_key($type),'status'=>'queued','source'=>sanitize_key($source),'created_at'=>$now,'updated_at'=>$now),array('%s','%s','%s','%s','%s','%s','%s'));
        return (int)$wpdb->insert_id;
    }

    private static function publish_local($title,$html,$type,$keyword) {
        $post_id = wp_insert_post(array('post_title'=>$title,'post_content'=>$html,'post_status'=>'publish','post_type'=>'post','post_author'=>get_current_user_id() ?: 1),true);
        if (is_wp_error($post_id)) return $post_id;
        update_post_meta($post_id,'_mutqan_content_type',$type);
        update_post_meta($post_id,'_mutqan_primary_keyword',$keyword);
        update_post_meta($post_id,'_mutqan_ai_generated',1);
        return (int)$post_id;
    }

    public static function process_one($id) {
        global $wpdb;
        $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::table()." WHERE id=%d",$id),ARRAY_A);
        if (!$row) return new WP_Error('not_found','المقال غير موجود.');
        if (!in_array($row['status'],array('queued','draft','needs_review'),true)) return new WP_Error('invalid_state','حالة المقال لا تسمح بالمعالجة.');
        $html=self::generate($row['specialty'],$row['keyword'],$row['title'],$row['article_type']);
        if (is_wp_error($html)) return $html;
        $score=self::quality($row['title'],$html,$row['keyword']);
        $min=(float)get_option('mutqan_content_min_quality',80);
        $status=$score >= $min ? 'ready' : 'needs_review';
        $wpdb->update(self::table(),array('status'=>$status,'quality_score'=>$score,'ai_generated'=>1,'updated_at'=>current_time('mysql')),array('id'=>$id),array('%s','%f','%d','%s'),array('%d'));
        update_option('mutqan_content_draft_'.$id,array('title'=>$row['title'],'content'=>$html),false);
        if ($status==='ready' && get_option('mutqan_content_auto_publish',0)) {
            $post=self::publish_local($row['title'],$html,$row['article_type'],$row['keyword']);
            if (!is_wp_error($post)) {
                $wpdb->update(self::table(),array('status'=>'published','post_id'=>$post,'updated_at'=>current_time('mysql')),array('id'=>$id),array('%s','%d','%s'),array('%d'));
            }
        }
        return array('id'=>(int)$id,'quality'=>(float)$score,'status'=>$status);
    }

    public static function run_queue() {
        global $wpdb;
        $limit=max(1,min(10,(int)get_option('mutqan_content_daily_limit',10)));
        $ids=$wpdb->get_col($wpdb->prepare("SELECT id FROM ".self::table()." WHERE status='queued' ORDER BY id ASC LIMIT %d",$limit));
        foreach($ids as $id) self::process_one((int)$id);
    }

    public static function rest() {
        register_rest_route('mutqan/v1','/content/queue',array(
            'methods'=>'GET','permission_callback'=>array(__CLASS__,'can'),
            'callback'=>function(){global $wpdb;return rest_ensure_response($wpdb->get_results("SELECT * FROM ".self::table()." ORDER BY id DESC LIMIT 100",ARRAY_A));}
        ));
        register_rest_route('mutqan/v1','/content/queue',array(
            'methods'=>'POST','permission_callback'=>array(__CLASS__,'can'),
            'callback'=>function($r){
                $p=$r->get_json_params();
                return rest_ensure_response(self::enqueue($p['specialty']??'',$p['keyword']??'',$p['title']??'', $p['article_type']??'guide','api'));
            }
        ));
        register_rest_route('mutqan/v1','/content/process/(?P<id>\d+)',array(
            'methods'=>'POST','permission_callback'=>array(__CLASS__,'can'),
            'callback'=>function($r){return rest_ensure_response(self::process_one((int)$r['id']));}
        ));
    }

    public static function page() {
        if (!self::can()) wp_die('غير مصرح');
        global $wpdb;
        if (isset($_POST['mq_enqueue']) && check_admin_referer('mq_content')) {
            self::enqueue(sanitize_text_field($_POST['specialty']??''),sanitize_text_field($_POST['keyword']??''),sanitize_text_field($_POST['title']??''),sanitize_key($_POST['article_type']??'guide'),'manual');
        }
        if (isset($_POST['mq_process']) && check_admin_referer('mq_content')) self::process_one(absint($_POST['mq_process']));
        $rows=$wpdb->get_results("SELECT * FROM ".self::table()." ORDER BY id DESC LIMIT 50",ARRAY_A);
        echo '<div class="wrap" dir="rtl"><h1>مُتقِن — مركز المحتوى و SEO</h1>';
        echo '<p>إدارة التخصصات والكلمات والعناوين والمقالات مع بوابة جودة وسجل نشر. WordPress REST API يدعم إنشاء وتحديث المقالات المصادق عليها. <a href="'.esc_url(admin_url('options-general.php?page=mutqan_content')).'">إعدادات الوحدة</a></p>';
        echo '<form method="post" style="background:#fff;padding:16px;margin:16px 0"><h2>إضافة مهمة محتوى</h2>';
        wp_nonce_field('mq_content');
        echo '<select name="specialty">';
        foreach(self::specialties() as $s) echo '<option value="'.esc_attr($s).'">'.esc_html($s).'</option>';
        echo '</select> ';
        echo '<input name="keyword" required placeholder="الكلمة / الموضوع" class="regular-text"> ';
        echo '<input name="title" required placeholder="العنوان" class="regular-text"> ';
        echo '<select name="article_type">';
        foreach(self::article_types() as $k=>$v) echo '<option value="'.esc_attr($k).'">'.esc_html($v).'</option>';
        echo '</select> <button class="button button-primary" name="mq_enqueue" value="1">إضافة للمحرك</button></form>';
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>التخصص</th><th>الكلمة</th><th>العنوان</th><th>النوع</th><th>الحالة</th><th>الجودة</th><th>إجراء</th></tr></thead><tbody>';
        foreach($rows as $x){
            echo '<tr><td>'.(int)$x['id'].'</td><td>'.esc_html($x['specialty']).'</td><td>'.esc_html($x['keyword']).'</td><td>'.esc_html($x['title']).'</td><td>'.esc_html($x['article_type']).'</td><td>'.esc_html($x['status']).'</td><td>'.esc_html($x['quality_score']).'</td><td><form method="post">'.wp_nonce_field('mq_content','_wpnonce',true,false).'<button class="button" name="mq_process" value="'.(int)$x['id'].'">توليد/فحص</button></form></td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
MUTQAN_Content_SEO::init();

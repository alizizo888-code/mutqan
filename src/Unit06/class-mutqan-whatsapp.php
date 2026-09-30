<?php
defined('ABSPATH') || exit;

/**
 * MUTQAN WhatsApp Business Center.
 *
 * Connector-neutral storage and REST boundary. The WordPress layer never
 * stores provider secrets in browser code. QR/Cloud/API connection is
 * represented as a connector mode and becomes SETUP REQUIRED until a real
 * provider adapter is configured.
 */
final class MUTQAN_WhatsApp {
    const VERSION = '1.0.0';

    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'rest'));
    }

    private static function table($name) {
        global $wpdb;
        return $wpdb->prefix . 'mutqan_whatsapp_' . $name;
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $sql = array();
        $sql[] = "CREATE TABLE " . self::table('accounts') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            account_name VARCHAR(190) NOT NULL,
            display_name VARCHAR(190) NULL,
            phone_number VARCHAR(40) NULL,
            country_code VARCHAR(10) NULL,
            account_type VARCHAR(30) NOT NULL DEFAULT 'business',
            connection_type VARCHAR(40) NOT NULL DEFAULT 'official_api',
            provider VARCHAR(100) NULL,
            business_id VARCHAR(190) NULL,
            phone_number_id VARCHAR(190) NULL,
            external_account_id VARCHAR(190) NULL,
            secret_ref VARCHAR(190) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'setup_required',
            qr_status VARCHAR(30) NOT NULL DEFAULT 'not_available',
            qr_session_id VARCHAR(190) NULL,
            webhook_status VARCHAR(30) NOT NULL DEFAULT 'not_configured',
            department VARCHAR(80) NULL,
            branch VARCHAR(120) NULL,
            ai_enabled TINYINT(1) NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            last_seen DATETIME NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY phone_number (phone_number),
            KEY department (department)
        ) $charset;";
        $sql[] = "CREATE TABLE " . self::table('contacts') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NULL,
            account_id BIGINT UNSIGNED NULL,
            phone_number VARCHAR(40) NOT NULL,
            display_name VARCHAR(190) NULL,
            source VARCHAR(40) NOT NULL DEFAULT 'whatsapp',
            first_seen DATETIME NULL,
            last_seen DATETIME NULL,
            metadata LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY account_id (account_id),
            KEY phone_number (phone_number)
        ) $charset;";
        $sql[] = "CREATE TABLE " . self::table('conversations') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id BIGINT UNSIGNED NULL,
            contact_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            order_id BIGINT UNSIGNED NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'open',
            assigned_user_id BIGINT UNSIGNED NULL,
            ai_enabled TINYINT(1) NOT NULL DEFAULT 0,
            last_message_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY customer_id (customer_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset;";
        $sql[] = "CREATE TABLE " . self::table('messages') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id BIGINT UNSIGNED NOT NULL,
            account_id BIGINT UNSIGNED NULL,
            direction VARCHAR(20) NOT NULL DEFAULT 'inbound',
            external_message_id VARCHAR(190) NULL,
            message_type VARCHAR(30) NOT NULL DEFAULT 'text',
            body LONGTEXT NULL,
            media_ref VARCHAR(190) NULL,
            sender_phone VARCHAR(40) NULL,
            recipient_phone VARCHAR(40) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'received',
            sent_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY external_message_id (external_message_id),
            KEY created_at (created_at)
        ) $charset;";
        $sql[] = "CREATE TABLE " . self::table('notifications') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id BIGINT UNSIGNED NULL,
            customer_id BIGINT UNSIGNED NULL,
            conversation_id BIGINT UNSIGNED NULL,
            order_id BIGINT UNSIGNED NULL,
            event_type VARCHAR(60) NOT NULL,
            title VARCHAR(190) NOT NULL,
            body LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'unread',
            created_at DATETIME NOT NULL,
            read_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY customer_id (customer_id),
            KEY status (status)
        ) $charset;";
        $sql[] = "CREATE TABLE " . self::table('routes') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id BIGINT UNSIGNED NOT NULL,
            priority INT NOT NULL DEFAULT 100,
            branch VARCHAR(120) NULL,
            city VARCHAR(120) NULL,
            service VARCHAR(120) NULL,
            default_route TINYINT(1) NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY priority (priority)
        ) $charset;";
        $sql[] = "CREATE TABLE " . self::table('audit') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id BIGINT UNSIGNED NULL,
            user_id BIGINT UNSIGNED NULL,
            action VARCHAR(80) NOT NULL,
            object_type VARCHAR(40) NULL,
            object_id BIGINT UNSIGNED NULL,
            details LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset;";
        foreach ($sql as $statement) {
            dbDelta($statement);
        }
    }

    private static function allowed() {
        return current_user_can('manage_options') || current_user_can('mutqan_manage_ai') || current_user_can('mutqan_manage_operations');
    }

    private static function audit($action, $account_id = 0, $details = array()) {
        global $wpdb;
        $wpdb->insert(self::table('audit'), array(
            'account_id' => absint($account_id),
            'user_id' => get_current_user_id() ?: null,
            'action' => sanitize_key($action),
            'object_type' => 'whatsapp',
            'object_id' => absint($account_id),
            'details' => wp_json_encode($details, JSON_UNESCAPED_UNICODE),
            'created_at' => current_time('mysql', true),
        ));
    }

    public static function rest() {
        register_rest_route('mutqan/v1', '/whatsapp/accounts', array(
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => array(__CLASS__, 'allowed'),
            'callback' => array(__CLASS__, 'list_accounts'),
        ));
        register_rest_route('mutqan/v1', '/whatsapp/accounts', array(
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => array(__CLASS__, 'allowed'),
            'callback' => array(__CLASS__, 'create_account'),
        ));
        register_rest_route('mutqan/v1', '/whatsapp/accounts/(?P<id>\\d+)/qr', array(
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => array(__CLASS__, 'allowed'),
            'callback' => array(__CLASS__, 'qr_status'),
        ));
        register_rest_route('mutqan/v1', '/whatsapp/summary', array(
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => array(__CLASS__, 'allowed'),
            'callback' => array(__CLASS__, 'summary'),
        ));
    }

    public static function list_accounts() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT id,account_name,display_name,phone_number,account_type,connection_type,provider,status,qr_status,webhook_status,department,branch,ai_enabled,active,last_seen,created_at,updated_at FROM " . self::table('accounts') . " ORDER BY active DESC,id DESC LIMIT 500", ARRAY_A);
        return rest_ensure_response(array('accounts' => $rows ?: array()));
    }

    public static function summary() {
        global $wpdb;
        $a=self::table('accounts');$c=self::table('contacts');$v=self::table('conversations');$m=self::table('messages');$n=self::table('notifications');
        return rest_ensure_response(array(
            'accounts'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $a WHERE active=1"),
            'connected'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $a WHERE active=1 AND status='connected'"),
            'contacts'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $c"),
            'conversations'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $v WHERE status='open'"),
            'messages'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $m"),
            'unread'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM $n WHERE status='unread'),
        ));
    }

    public static function create_account(WP_REST_Request $request) {
        global $wpdb;
        $p=$request->get_json_params();
        $name=sanitize_text_field($p['account_name']??'');
        if($name==='') return new WP_Error('account_name_required','اسم الحساب مطلوب.',array('status'=>400));
        $type=in_array(($p['account_type']??'business'),array('business','personal'),true)?$p['account_type']:'business';
        $connection=in_array(($p['connection_type']??'official_api'),array('official_api','provider','qr_connector'),true)?$p['connection_type']:'official_api';
        $now=current_time('mysql',true);
        $data=array(
            'account_name'=>$name,
            'display_name'=>sanitize_text_field($p['display_name']??''),
            'phone_number'=>sanitize_text_field($p['phone_number']??''),
            'country_code'=>sanitize_text_field($p['country_code']??''),
            'account_type'=>$type,
            'connection_type'=>$connection,
            'provider'=>sanitize_text_field($p['provider']??''),
            'business_id'=>sanitize_text_field($p['business_id']??''),
            'phone_number_id'=>sanitize_text_field($p['phone_number_id']??''),
            'external_account_id'=>sanitize_text_field($p['external_account_id']??''),
            'secret_ref'=>sanitize_text_field($p['secret_ref']??''),
            'status'=>'setup_required',
            'qr_status'=>$connection==='qr_connector'?'waiting':'not_available',
            'webhook_status'=>'not_configured',
            'department'=>sanitize_text_field($p['department']??''),
            'branch'=>sanitize_text_field($p['branch']??''),
            'ai_enabled'=>!empty($p['ai_enabled'])?1:0,
            'active'=>1,
            'created_by'=>get_current_user_id()?:null,
            'created_at'=>$now,
            'updated_at'=>$now,
        );
        $ok=$wpdb->insert(self::table('accounts'),$data);
        if(!$ok)return new WP_Error('account_create_failed','تعذر إنشاء حساب WhatsApp.',array('status'=>500));
        $id=(int)$wpdb->insert_id;
        self::audit('account_created',$id,array('connection_type'=>$connection,'account_type'=>$type));
        return rest_ensure_response(array('id'=>$id,'status'=>'setup_required','qr_status'=>$data['qr_status']));
    }

    public static function qr_status(WP_REST_Request $request) {
        global $wpdb;
        $id=absint($request['id']);
        $account=$wpdb->get_row($wpdb->prepare("SELECT id,connection_type,qr_status,status,qr_session_id FROM ".self::table('accounts')." WHERE id=%d",$id),ARRAY_A);
        if(!$account)return new WP_Error('not_found','الحساب غير موجود.',array('status'=>404));
        if($account['connection_type']!=='qr_connector')return new WP_Error('qr_not_supported','هذا الحساب لا يستخدم موصل QR.',array('status'=>409));
        return rest_ensure_response(array(
            'id'=>$id,
            'status'=>$account['status'],
            'qr_status'=>$account['qr_status'],
            'qr_session_id'=>$account['qr_session_id'],
            'setup_required'=>true,
            'message'=>'يلزم موصل QR فعلي لإصدار رمز وربط الجهاز. لم يتم توليد QR وهمي.'
        ));
    }

    public static function table_name($name) { return self::table($name); }
}
MUTQAN_WhatsApp::init();

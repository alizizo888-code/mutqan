<?php
defined('ABSPATH') || exit;

final class MUTQAN_Permissions_Admin {
    public static function init(){ add_action('admin_menu',array(__CLASS__,'menu'),25); add_action('admin_post_mutqan_permissions_save',array(__CLASS__,'save')); }
    public static function caps(){
        return array(
            'mutqan_view_users'=>'عرض المستخدمين','mutqan_add_users'=>'إضافة المستخدمين','mutqan_edit_users'=>'تعديل المستخدمين','mutqan_delete_users'=>'حذف المستخدمين','mutqan_manage_roles'=>'إدارة الأدوار',
            'mutqan_view_orders'=>'عرض الطلبات','mutqan_add_orders'=>'إضافة الطلبات','mutqan_edit_orders'=>'تعديل الطلبات','mutqan_delete_orders'=>'حذف الطلبات','mutqan_approve_orders'=>'اعتماد الطلبات','mutqan_view_own_orders'=>'عرض طلباتي','mutqan_edit_own_orders'=>'تعديل طلباتي',
            'mutqan_view_customers'=>'عرض العملاء','mutqan_add_customers'=>'إضافة العملاء','mutqan_edit_customers'=>'تعديل العملاء','mutqan_delete_customers'=>'حذف العملاء',
            'mutqan_manage_operations'=>'إدارة العمليات','mutqan_view_own_profile'=>'عرض الملف الشخصي','mutqan_edit_own_profile'=>'تعديل الملف الشخصي',
            'mutqan_manage_pricing'=>'إدارة الأسعار','mutqan_manage_invoices'=>'إدارة الفواتير','mutqan_manage_settings'=>'إدارة الإعدادات','mutqan_view_audit'=>'عرض التدقيق','mutqan_manage_ai'=>'إدارة الذكاء الاصطناعي','mutqan_view_analytics'=>'عرض التحليلات','mutqan_manage_content'=>'إدارة المحتوى و SEO'
        );
    }
    public static function roles(){return array('mutqan_site_admin'=>'مشرف الموقع','mutqan_operations'=>'مشرف العمليات','mutqan_technician'=>'الفني','mutqan_customer'=>'العميل','mutqan_partner'=>'شريك المهنة');}
    public static function menu(){add_submenu_page('mutqan','Permissions','Permissions','mutqan_manage_roles','mutqan-permissions',array(__CLASS__,'page'));}
    public static function page(){
        if(!current_user_can('mutqan_manage_roles'))wp_die('Unauthorized');
        $caps=self::caps(); $roles=self::roles(); ?>
        <div class="wrap" dir="rtl"><h1>مُتقِن — مصفوفة الصلاحيات</h1><p>الصلاحيات حقيقية على مستوى WordPress. المالك لا يظهر هنا لأنه يملك كل صلاحيات MUTQAN دائمًا.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="mutqan_permissions_save"><?php wp_nonce_field('mutqan_permissions_save'); ?>
        <table class="widefat striped"><thead><tr><th>الصلاحية</th><?php foreach($roles as $slug=>$name): ?><th><?php echo esc_html($name); ?></th><?php endforeach; ?></tr></thead><tbody>
        <?php foreach($caps as $cap=>$label): ?><tr><td><strong><?php echo esc_html($label); ?></strong><br><code><?php echo esc_html($cap); ?></code></td><?php foreach($roles as $slug=>$name): $role=get_role($slug); ?><td><input type="checkbox" name="caps[<?php echo esc_attr($slug); ?>][]" value="<?php echo esc_attr($cap); ?>" <?php checked($role && $role->has_cap($cap)); ?>></td><?php endforeach; ?></tr><?php endforeach; ?>
        </tbody></table><?php submit_button('حفظ الصلاحيات'); ?></form></div><?php
    }
    public static function save(){
        if(!current_user_can('mutqan_manage_roles'))wp_die('Unauthorized');
        check_admin_referer('mutqan_permissions_save');
        $submitted=isset($_POST['caps'])?(array)wp_unslash($_POST['caps']):array();
        foreach(self::roles() as $slug=>$name){
            $role=get_role($slug); if(!$role)continue;
            foreach(array_keys(self::caps()) as $cap){
                if(isset($submitted[$slug]) && in_array($cap,(array)$submitted[$slug],true))$role->add_cap($cap);
                else $role->remove_cap($cap);
            }
        }
        MUTQAN_Audit::log('permissions_updated','roles',0,array('actor'=>get_current_user_id()));
        wp_safe_redirect(add_query_arg(array('page'=>'mutqan-permissions','updated'=>'1'),admin_url('admin.php'))); exit;
    }
}
MUTQAN_Permissions_Admin::init();

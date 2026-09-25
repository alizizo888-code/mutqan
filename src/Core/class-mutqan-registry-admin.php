<?php
defined('ABSPATH') || exit;

final class MUTQAN_Registry_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__,'menu'), 20);
        add_action('admin_post_mutqan_registry_save', array(__CLASS__,'save'));
    }

    public static function menu() {
        add_submenu_page('mutqan','Dynamic Registry','Dynamic Registry','mutqan_manage_settings','mutqan-registry',array(__CLASS__,'page'));
    }

    public static function page() {
        if (!current_user_can('mutqan_manage_settings')) wp_die('Unauthorized');
        $r=MUTQAN_Registry::get(); ?>
        <div class="wrap" dir="rtl"><h1>مُتقِن — السجل الديناميكي</h1>
        <p>هذا السجل يحدد الوحدات والأدوار والتنقل. التعديل هنا لا يغير Core ولا يعيد بناء النظام.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="mutqan_registry_save"><?php wp_nonce_field('mutqan_registry_save'); ?>
        <h2>الوحدات</h2><table class="widefat striped"><thead><tr><th>المعرف</th><th>الاسم</th><th>الحالة</th></tr></thead><tbody>
        <?php foreach($r['units'] as $id=>$u): ?><tr><td><?php echo esc_html($id); ?></td><td><input class="regular-text" name="units[<?php echo esc_attr($id); ?>][name]" value="<?php echo esc_attr($u['name']); ?>"></td><td><select name="units[<?php echo esc_attr($id); ?>][status]"><?php foreach(array('active','disabled','setup_required') as $s): ?><option value="<?php echo esc_attr($s); ?>" <?php selected($u['status'],$s); ?>><?php echo esc_html($s); ?></option><?php endforeach; ?></select></td></tr><?php endforeach; ?>
        </tbody></table>
        <h2>الأدوار والتنقل</h2><table class="widefat striped"><thead><tr><th>الدور</th><th>الواجهة الرئيسية</th><th>عناصر التنقل</th></tr></thead><tbody>
        <?php foreach($r['roles'] as $role=>$cfg): ?><tr><td><?php echo esc_html($role); ?></td><td><input name="roles[<?php echo esc_attr($role); ?>][home]" value="<?php echo esc_attr($cfg['home']); ?>"></td><td><input class="large-text" name="roles[<?php echo esc_attr($role); ?>][nav]" value="<?php echo esc_attr(implode(',',$cfg['nav'])); ?>"></td></tr><?php endforeach; ?>
        </tbody></table><?php submit_button('حفظ السجل الديناميكي'); ?></form></div><?php
    }

    public static function save() {
        if(!current_user_can('mutqan_manage_settings')) wp_die('Unauthorized');
        check_admin_referer('mutqan_registry_save');
        $current=MUTQAN_Registry::get();
        $input=isset($_POST['units']) ? wp_unslash($_POST['units']) : array();
        foreach($current['units'] as $id=>$u) {
            if(isset($input[$id])) {
                $current['units'][$id]['name']=sanitize_text_field($input[$id]['name']??$u['name']);
                $status=sanitize_key($input[$id]['status']??$u['status']);
                $current['units'][$id]['status']=in_array($status,array('active','disabled','setup_required'),true)?$status:$u['status'];
            }
        }
        $roles=isset($_POST['roles']) ? wp_unslash($_POST['roles']) : array();
        foreach($current['roles'] as $role=>$cfg) {
            if(isset($roles[$role])) {
                $current['roles'][$role]['home']=sanitize_key($roles[$role]['home']??$cfg['home']);
                $nav=array_map('sanitize_key',array_filter(array_map('trim',explode(',',(string)($roles[$role]['nav']??'')))));
                $current['roles'][$role]['nav']=$nav;
            }
        }
        MUTQAN_Registry::set($current);
        MUTQAN_Audit::log('registry_updated','registry',0,array('actor'=>get_current_user_id()));
        wp_safe_redirect(add_query_arg(array('page'=>'mutqan-registry','updated'=>'1'),admin_url('admin.php')));
        exit;
    }
}
MUTQAN_Registry_Admin::init();

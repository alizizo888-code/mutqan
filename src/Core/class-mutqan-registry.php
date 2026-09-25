<?php
defined('ABSPATH') || exit;

final class MUTQAN_Registry {
    const OPTION = 'mutqan_registry';

    public static function init() {
        add_action('init', array(__CLASS__, 'register_defaults'), 5);
    }

    public static function defaults() {
        return array(
            'units' => array(
                'unit01' => array('name'=>'المستخدمون والهوية','status'=>'active'),
                'unit02' => array('name'=>'العمليات واللوجستيات','status'=>'active'),
                'unit03' => array('name'=>'الخدمات والعملاء','status'=>'active'),
                'unit04' => array('name'=>'الماليات والمبيعات','status'=>'active'),
                'unit05' => array('name'=>'التسويق والولاء','status'=>'active'),
                'unit06' => array('name'=>'الجودة والأمان والذكاء الاصطناعي','status'=>'active'),
            ),
            'roles' => array(
                'visitor'=>array('home'=>'customer','nav'=>array('home','services','help')),
                'mutqan_customer'=>array('home'=>'customer','nav'=>array('home','orders','offers','account')),
                'mutqan_technician'=>array('home'=>'technician','nav'=>array('home','tasks','wallet','account')),
                'mutqan_operations'=>array('home'=>'operations','nav'=>array('home','radar','orders','account')),
                'mutqan_site_admin'=>array('home'=>'admin','nav'=>array('home','operations','users','settings')),
                'mutqan_owner'=>array('home'=>'admin','nav'=>array('home','operations','users','settings','audit')),
                'mutqan_partner'=>array('home'=>'partner','nav'=>array('home','orders','account')),
            ),
        );
    }

    public static function get() {
        return wp_parse_args((array)get_option(self::OPTION, array()), self::defaults());
    }

    public static function register_defaults() {
        if (!get_option(self::OPTION, false)) {
            add_option(self::OPTION, self::defaults(), '', false);
        }
    }

    public static function set($data) {
        return update_option(self::OPTION, wp_parse_args($data, self::defaults()), false);
    }

    public static function unit($id) {
        $r=self::get(); return isset($r['units'][$id]) ? $r['units'][$id] : null;
    }

    public static function role_config($role) {
        $r=self::get(); return isset($r['roles'][$role]) ? $r['roles'][$role] : $r['roles']['visitor'];
    }
}
MUTQAN_Registry::init();

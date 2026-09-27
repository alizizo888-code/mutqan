<?php
defined('ABSPATH') || exit;

/**
 * MUTQAN portal isolation.
 * Every front-end portal has one owning role. Direct URL access is blocked too.
 */
final class MUTQAN_Portal_Guard {
    private static function map() {
        return array(
            'mutqan-customer' => array('mutqan_customer'),
            'mutqan-customer-services' => array('mutqan_customer'),
            'mutqan-technician' => array('mutqan_technician'),
            'mutqan-operations' => array('mutqan_operations'),
            'mutqan-supervisor' => array('mutqan_supervisor'),
            'mutqan-manager' => array('mutqan_manager'),
            'mutqan-owner' => array('mutqan_owner'),
            'mutqan-site-supervisor' => array('mutqan_site_admin'),
            'mutqan-control' => array('mutqan_owner', 'mutqan_site_admin'),
        );
    }

    public static function init() {
        add_action('template_redirect', array(__CLASS__, 'guard'), 1);
    }

    public static function role() {
        if (!is_user_logged_in()) return 'visitor';
        $user = wp_get_current_user();
        foreach (array('mutqan_owner','mutqan_site_admin','mutqan_manager','mutqan_supervisor','mutqan_operations','mutqan_technician','mutqan_customer','mutqan_partner') as $role) {
            if (in_array($role, (array) $user->roles, true)) return $role;
        }
        return current_user_can('manage_options') ? 'wp_admin' : 'visitor';
    }

    public static function allowed($slug) {
        $map = self::map();
        if (!isset($map[$slug])) return true;
        if (current_user_can('manage_options')) return true;
        return in_array(self::role(), $map[$slug], true);
    }

    public static function guard() {
        if (!is_page()) return;
        $slug = get_post_field('post_name', get_queried_object_id());
        if (!isset(self::map()[$slug]) || self::allowed($slug)) return;

        nocache_headers();

        if (!is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(get_permalink()));
            exit;
        }

        $home = get_page_by_path('mutqan-home');
        $target = $home ? get_permalink($home) : home_url('/');
        wp_safe_redirect($target);
        exit;
    }

    public static function expected_view($slug) {
        $map = array(
            'mutqan-home'=>'home',
            'mutqan-customer'=>'customer',
            'mutqan-customer-services'=>'customer_services',
            'mutqan-technician'=>'technician',
            'mutqan-operations'=>'operations',
            'mutqan-supervisor'=>'supervisor',
            'mutqan-manager'=>'manager',
            'mutqan-owner'=>'owner',
            'mutqan-site-supervisor'=>'site_admin',
            'mutqan-control'=>'control',
        );
        return isset($map[$slug]) ? $map[$slug] : '';
    }
}
MUTQAN_Portal_Guard::init();

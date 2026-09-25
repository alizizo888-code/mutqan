<?php
defined('ABSPATH') || exit;

final class MUTQAN_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
    }

    public static function menu() {
        add_menu_page(
            'MUTQAN',
            'MUTQAN',
            'mutqan_view_users',
            'mutqan',
            array(__CLASS__, 'dashboard'),
            'dashicons-admin-generic',
            3
        );
        add_submenu_page('mutqan','Users & Roles','Users & Roles','mutqan_view_users','mutqan-users',array(__CLASS__,'users'));
    }

    public static function dashboard() {
        if (!current_user_can('mutqan_view_users')) wp_die('Unauthorized');
        echo '<div class="wrap"><h1>MUTQAN Control Center</h1>';
        echo '<p>Unified core is active. External services remain setup-dependent.</p>';
        echo '<p><strong>OTP:</strong> Disabled by default.</p>';
        echo '</div>';
    }

    public static function users() {
        if (!current_user_can('mutqan_view_users')) wp_die('Unauthorized');
        $roles = wp_roles()->roles;
        echo '<div class="wrap"><h1>MUTQAN Users & Roles</h1><table class="widefat striped"><thead><tr><th>Role</th><th>Users</th></tr></thead><tbody>';
        foreach (array('mutqan_owner','mutqan_site_admin','mutqan_operations','mutqan_technician','mutqan_customer','mutqan_partner') as $role) {
            $count = count(get_users(array('role'=>$role,'fields'=>'ID')));
            echo '<tr><td>'.esc_html($roles[$role]['name'] ?? $role).'</td><td>'.esc_html($count).'</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}

MUTQAN_Admin::init();

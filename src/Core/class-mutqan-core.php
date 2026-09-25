<?php
defined('ABSPATH') || exit;

final class MUTQAN_Core {
    public static function boot() {
        self::register_roles();
        self::register_caps();
        self::register_rest();
    }

    public static function register_roles() {
        $roles = array(
            'mutqan_owner' => 'MUTQAN Owner',
            'mutqan_site_admin' => 'MUTQAN Site Admin',
            'mutqan_operations' => 'MUTQAN Operations',
            'mutqan_technician' => 'MUTQAN Technician',
            'mutqan_customer' => 'MUTQAN Customer',
            'mutqan_partner' => 'MUTQAN Partner',
        );
        foreach ($roles as $slug => $name) {
            if (!get_role($slug)) {
                add_role($slug, $name, array('read' => true));
            }
        }
    }

    public static function register_caps() {
        $caps = array(
            'mutqan_view_users','mutqan_add_users','mutqan_edit_users','mutqan_delete_users',
            'mutqan_manage_roles','mutqan_view_orders','mutqan_add_orders','mutqan_edit_orders',
            'mutqan_delete_orders','mutqan_approve_orders','mutqan_view_own_orders',
            'mutqan_edit_own_orders','mutqan_view_customers','mutqan_add_customers',
            'mutqan_edit_customers','mutqan_delete_customers','mutqan_manage_operations',
            'mutqan_view_own_profile','mutqan_edit_own_profile','mutqan_manage_pricing',
            'mutqan_manage_invoices','mutqan_manage_settings','mutqan_view_audit',
            'mutqan_manage_ai','mutqan_manage_marketing','mutqan_manage_finance','mutqan_manage_accounting','mutqan_manage_expenses','mutqan_manage_settlements','mutqan_manage_wallets','mutqan_manage_commissions','mutqan_manage_technicians','mutqan_manage_dispatch'
        );
        $owner = get_role('mutqan_owner');
        if ($owner) {
            foreach ($caps as $cap) {
                $owner->add_cap($cap);
            }
        }
    }

    public static function register_rest() {
        add_action('rest_api_init', function () {
            register_rest_route('mutqan/v1', '/status', array(
                'methods' => WP_REST_Server::READABLE,
                'permission_callback' => function () {
                    return current_user_can('mutqan_view_audit') || current_user_can('manage_options');
                },
                'callback' => function () {
                    return rest_ensure_response(array(
                        'ok' => true,
                        'version' => MUTQAN_VERSION,
                        'plugin' => 'MUTQAN Unified System'
                    ));
                }
            ));
        });
    }
}

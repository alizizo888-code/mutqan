<?php
defined('ABSPATH') || exit;

final class MUTQAN_Radar {
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'rest'));
        add_action('mutqan_event_order_assigned', array(__CLASS__, 'assignment_event'), 10, 1);
    }

    public static function technicians() {
        $ids = get_users(array('role' => 'mutqan_technician', 'fields' => 'ID', 'number' => 500));
        $out = array();
        foreach ($ids as $id) {
            $snapshot = MUTQAN_Dispatch::technician_snapshot($id);
            if ($snapshot) {
                $out[] = $snapshot;
            }
        }
        return $out;
    }

    public static function orders() {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT id,type,status,customer_id,technician_id,lat,lng,priority,created_at,updated_at
             FROM ".MUTQAN_Operations::table()."
             WHERE status NOT IN ('closed','cancelled')
             ORDER BY FIELD(priority,'urgent','high','normal','low'), created_at DESC
             LIMIT 500",
            ARRAY_A
        );
        return array_map(function($row) {
            $row['id'] = (int)$row['id'];
            $row['customer_id'] = (int)$row['customer_id'];
            $row['technician_id'] = (int)$row['technician_id'];
            $row['lat'] = $row['lat'] === null ? null : (float)$row['lat'];
            $row['lng'] = $row['lng'] === null ? null : (float)$row['lng'];
            return $row;
        }, $rows);
    }

    public static function summary() {
        global $wpdb;
        $table = MUTQAN_Operations::table();
        $counts = $wpdb->get_results(
            "SELECT status, COUNT(*) count FROM {$table} WHERE status NOT IN ('closed','cancelled') GROUP BY status",
            ARRAY_A
        );
        $status = array();
        foreach ($counts as $row) {
            $status[sanitize_key($row['status'])] = (int)$row['count'];
        }

        $techs = self::technicians();
        $available = $busy = $offline = 0;
        foreach ($techs as $tech) {
            if ($tech['status'] === 'available' && $tech['available_capacity'] > 0) $available++;
            elseif ($tech['status'] === 'offline') $offline++;
            else $busy++;
        }

        return array(
            'orders' => $status,
            'technicians' => array(
                'total' => count($techs),
                'available' => $available,
                'busy' => $busy,
                'offline' => $offline
            ),
            'generated_at' => current_time('mysql', true)
        );
    }

    public static function assignment_event($payload) {
        if (!empty($payload['order_id'])) {
            MUTQAN_Audit::log('radar_assignment_event', 'order', (int)$payload['order_id'], $payload);
        }
    }

    public static function rest() {
        register_rest_route('mutqan/v1', '/radar', array(
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => function() {
                return current_user_can('mutqan_manage_operations') || current_user_can('mutqan_view_orders');
            },
            'callback' => function() {
                return rest_ensure_response(array(
                    'summary' => self::summary(),
                    'technicians' => self::technicians(),
                    'orders' => self::orders()
                ));
            }
        ));

        register_rest_route('mutqan/v1', '/radar/technicians', array(
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => function() {
                return current_user_can('mutqan_manage_operations');
            },
            'callback' => function() {
                return rest_ensure_response(self::technicians());
            }
        ));

        register_rest_route('mutqan/v1', '/radar/orders', array(
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => function() {
                return current_user_can('mutqan_manage_operations') || current_user_can('mutqan_view_orders');
            },
            'callback' => function() {
                return rest_ensure_response(self::orders());
            }
        ));
    }
}

MUTQAN_Radar::init();

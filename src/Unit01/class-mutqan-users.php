<?php
defined('ABSPATH') || exit;

final class MUTQAN_Users {
    const PHONE_META = '_mutqan_phone';
    const APPROVAL_META = '_mutqan_technician_status';
    const SOURCE_META = '_mutqan_source';
    const DOCS_META = '_mutqan_private_documents';

    public static function init() {
        add_action('show_user_profile', array(__CLASS__, 'profile_fields'));
        add_action('edit_user_profile', array(__CLASS__, 'profile_fields'));
        add_action('personal_options_update', array(__CLASS__, 'save_profile'));
        add_action('edit_user_profile_update', array(__CLASS__, 'save_profile'));
    }

    public static function profile_fields($user) {
        if (!current_user_can('mutqan_view_users') && get_current_user_id() !== (int) $user->ID) return;
        $phone = get_user_meta($user->ID, self::PHONE_META, true);
        $status = get_user_meta($user->ID, self::APPROVAL_META, true);
        if (!$status) $status = 'pending';
        ?>
        <h2>MUTQAN Identity</h2>
        <table class="form-table">
            <tr><th><label>Phone</label></th><td>
                <input type="text" name="mutqan_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" <?php disabled(!current_user_can('mutqan_edit_own_profile') && !current_user_can('mutqan_edit_users')); ?> />
            </td></tr>
            <?php if (in_array('mutqan_technician', (array)$user->roles, true) && current_user_can('mutqan_edit_users')): ?>
            <tr><th><label>Technician status</label></th><td>
                <select name="mutqan_technician_status">
                    <?php foreach (array('pending','approved','rejected','needs_completion','suspended') as $value): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>><?php echo esc_html($value); ?></option>
                    <?php endforeach; ?>
                </select>
            </td></tr>
            <?php endif; ?>
        </table>
        <?php
    }

    public static function save_profile($user_id) {
        if (!current_user_can('edit_user', $user_id)) return;
        if (isset($_POST['mutqan_phone']) && (current_user_can('mutqan_edit_users') || current_user_can('mutqan_edit_own_profile'))) {
            $phone = self::normalize_phone(wp_unslash($_POST['mutqan_phone']));
            if ($phone !== '') update_user_meta($user_id, self::PHONE_META, $phone);
        }
        if (current_user_can('mutqan_edit_users') && isset($_POST['mutqan_technician_status'])) {
            $allowed = array('pending','approved','rejected','needs_completion','suspended');
            $status = sanitize_key(wp_unslash($_POST['mutqan_technician_status']));
            if (in_array($status, $allowed, true)) update_user_meta($user_id, self::APPROVAL_META, $status);
        }
    }

    public static function normalize_phone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', (string)$phone);
        return substr($phone, 0, 32);
    }

    public static function find_by_phone($phone) {
        $phone = self::normalize_phone($phone);
        if (!$phone) return 0;
        $users = get_users(array(
            'meta_key' => self::PHONE_META,
            'meta_value' => $phone,
            'number' => 1,
            'fields' => 'ID'
        ));
        return !empty($users) ? (int)$users[0] : 0;
    }

    public static function create_customer($name, $phone, $source='direct') {
        $phone = self::normalize_phone($phone);
        if (!$phone) return new WP_Error('invalid_phone', 'Phone is required.');
        $existing = self::find_by_phone($phone);
        if ($existing) return $existing;

        $base = 'customer_' . preg_replace('/[^0-9]/', '', $phone);
        $login = sanitize_user($base, true);
        if (username_exists($login)) $login .= '_' . wp_generate_password(4, false, false);

        $user_id = wp_insert_user(array(
            'user_login' => $login,
            'user_pass' => wp_generate_password(32, true, true),
            'display_name' => sanitize_text_field($name),
            'role' => 'mutqan_customer'
        ));
        if (is_wp_error($user_id)) return $user_id;

        update_user_meta($user_id, self::PHONE_META, $phone);
        update_user_meta($user_id, self::SOURCE_META, sanitize_key($source));
        return (int)$user_id;
    }

    public static function technician_can_receive($user_id) {
        $user = get_userdata($user_id);
        if (!$user || !in_array('mutqan_technician', (array)$user->roles, true)) return false;
        return get_user_meta($user_id, self::APPROVAL_META, true) === 'approved';
    }
}

MUTQAN_Users::init();

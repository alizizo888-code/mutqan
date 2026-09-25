<?php
defined('ABSPATH') || exit;

final class MUTQAN_Readiness {
    const OPTION='mutqan_readiness';

    public static function set($key,$status,$message='') {
        $data=(array)get_option(self::OPTION,array());
        $data[sanitize_key($key)]=array('status'=>sanitize_key($status),'message'=>sanitize_text_field($message),'updated_at'=>current_time('mysql',true));
        update_option(self::OPTION,$data,false);
    }

    public static function all() { return (array)get_option(self::OPTION,array()); }
}

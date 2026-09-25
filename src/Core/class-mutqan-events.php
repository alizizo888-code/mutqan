<?php
defined('ABSPATH') || exit;

final class MUTQAN_Events {
    public static function emit($event, $payload=array()) {
        $event = sanitize_key($event);
        do_action('mutqan_event_'.$event, $payload);
        do_action('mutqan_event', $event, $payload);
    }

    public static function listen($event, $callback, $priority=10, $accepted_args=1) {
        add_action('mutqan_event_'.sanitize_key($event), $callback, $priority, $accepted_args);
    }
}

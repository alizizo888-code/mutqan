<?php
defined('ABSPATH') || exit;
final class MUTQAN_System {
 public static function init(){add_action('rest_api_init',array(__CLASS__,'rest'));}
 public static function rest(){register_rest_route('mutqan/v1','/readiness',array('methods'=>'GET','permission_callback'=>function(){return current_user_can('mutqan_view_audit')||current_user_can('manage_options');},'callback'=>function(){return rest_ensure_response(array('core'=>true,'otp'=>false,'maps'=>'setup_required','ai'=>'setup_required','payments'=>'setup_required','messaging'=>'setup_required'));}));}
}
MUTQAN_System::init();

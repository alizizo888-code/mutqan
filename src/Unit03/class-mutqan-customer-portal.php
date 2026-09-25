<?php
defined('ABSPATH') || exit;

final class MUTQAN_Customer_Portal {
 public static function init(){add_action('rest_api_init',array(__CLASS__,'rest'));add_shortcode('mutqan_customer_portal',array(__CLASS__,'shortcode'));}
 private static function identity($name,$phone,$source){
  $phone=MUTQAN_Users::normalize_phone($phone);
  if(strlen(preg_replace('/\D/','',$phone))<8)return new WP_Error('invalid_phone','رقم الجوال غير صالح.',array('status'=>400));
  if(is_user_logged_in()){
   $uid=get_current_user_id();update_user_meta($uid,MUTQAN_Users::PHONE_META,$phone);return $uid;
  }
  return MUTQAN_Users::create_customer($name,$phone,$source);
 }
 public static function create_order($p,$source='customer'){
  global $wpdb;
  $name=sanitize_text_field($p['name']??'');$phone=sanitize_text_field($p['phone']??'');
  if(strlen($name)<2||strlen(preg_replace('/\D/','',$phone))<8)return new WP_Error('invalid_identity','الاسم ورقم الجوال مطلوبان.',array('status'=>400));
  $uid=self::identity($name,$phone,$source);if(is_wp_error($uid))return $uid;
  $service_id=absint($p['service_id']??0);
  $service=$service_id?$wpdb->get_row($wpdb->prepare("SELECT * FROM ".MUTQAN_Services::table()." WHERE id=%d AND status='active'",$service_id),ARRAY_A):null;
  if($service_id&&!$service)return new WP_Error('service_not_found','الخدمة غير متاحة.',array('status'=>404));
  $base=$service?(float)$service['price']:0;$price=MUTQAN_Pricing::calculate($service_id,$base);
  $lat=isset($p['lat'])?(float)$p['lat']:null;$lng=isset($p['lng'])?(float)$p['lng']:null;
  if($lat===null||$lng===null)return new WP_Error('location_required','موقع الخدمة مطلوب.',array('status'=>400));
  $payload=array('source'=>$source,'service_id'=>$service_id,'service_name'=>$service?$service['name']:'','details'=>sanitize_textarea_field($p['details']??''),'location_label'=>sanitize_text_field($p['location_label']??''),'quoted_price'=>$price,'specialty'=>sanitize_key($p['specialty']??''),'preferred_time'=>sanitize_text_field($p['preferred_time']??''));
  $now=current_time('mysql',true);
  $ok=$wpdb->insert(MUTQAN_Operations::table(),array('type'=>'service','status'=>'new','customer_id'=>(int)$uid,'technician_id'=>0,'lat'=>$lat,'lng'=>$lng,'priority'=>in_array(($p['priority']??'normal'),array('low','normal','high','urgent'),true)?sanitize_key($p['priority']):'normal','payload'=>wp_json_encode($payload),'created_at'=>$now,'updated_at'=>$now));
  if(!$ok)return new WP_Error('order_create_failed','تعذر إنشاء الطلب.',array('status'=>500));
  $id=(int)$wpdb->insert_id;
  if(class_exists('MUTQAN_Billing')){
   $wpdb->insert(MUTQAN_Billing::table(),array('order_id'=>$id,'customer_id'=>(int)$uid,'subtotal'=>$base,'discount'=>0,'tax'=>0,'total'=>$price,'status'=>'issued','payment_status'=>'unpaid','currency'=>'SAR','notes'=>'Auto-created from customer request','created_at'=>$now,'updated_at'=>$now));
  }
  MUTQAN_Audit::log('customer_order_created','order',$id,array('customer_id'=>(int)$uid,'service_id'=>$service_id,'source'=>$source));
  MUTQAN_Events::emit('order_created',array('order_id'=>$id,'customer_id'=>(int)$uid,'source'=>$source));
  return array('id'=>$id,'customer_id'=>(int)$uid,'status'=>'new','service'=>$service?$service['name']:'','quoted_price'=>$price,'currency'=>'SAR');
 }
 public static function rest(){
  register_rest_route('mutqan/v1','/customer/services',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function(){global $wpdb;return rest_ensure_response($wpdb->get_results("SELECT id,name,description,price FROM ".MUTQAN_Services::table()." WHERE status='active' ORDER BY name ASC",ARRAY_A));}));
  register_rest_route('mutqan/v1','/customer/orders',array('methods'=>'POST','permission_callback'=>function(){return is_user_logged_in();},'callback'=>function($r){return rest_ensure_response(self::create_order($r->get_json_params(),'customer'));}));
  register_rest_route('mutqan/v1','/public/customer-order',array('methods'=>'POST','permission_callback'=>'__return_true','callback'=>function($r){$ip=sanitize_text_field($_SERVER['REMOTE_ADDR']??'unknown');$key='mq_public_customer_order_'.md5($ip);$n=(int)get_transient($key);if($n>=5)return new WP_Error('rate_limited','طلبات كثيرة، حاول لاحقاً.',array('status'=>429));set_transient($key,$n+1,HOUR_IN_SECONDS);return rest_ensure_response(self::create_order($r->get_json_params(),'visitor'));}));
  register_rest_route('mutqan/v1','/customer/orders',array('methods'=>'GET','permission_callback'=>function(){return is_user_logged_in();},'callback'=>function(){global $wpdb;$uid=get_current_user_id();$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM ".MUTQAN_Operations::table()." WHERE customer_id=%d ORDER BY id DESC LIMIT 50",$uid),ARRAY_A);foreach($rows as &$row)$row['payload']=$row['payload']?json_decode($row['payload'],true):array();return rest_ensure_response($rows);}));
 }
 public static function shortcode(){ob_start();?><section class="mq-customer-portal" dir="rtl"><h2>طلب خدمة</h2><p>حدد الخدمة والموقع وأرسل طلبك إلى غرفة العمليات.</p><form data-mq-order-form><input name="name" placeholder="الاسم" required value="<?php echo is_user_logged_in()?esc_attr(wp_get_current_user()->display_name):'';?>"><input name="phone" placeholder="رقم الجوال" required><select name="service_id" data-mq-services required><option value="">جاري تحميل الخدمات...</option></select><textarea name="details" placeholder="وصف المشكلة أو المطلوب"></textarea><input name="location_label" placeholder="وصف الموقع"><input name="lat" type="hidden"><input name="lng" type="hidden"><button type="button" data-mq-location>تحديد موقعي</button><button type="submit">إرسال الطلب</button><div data-mq-order-result></div></form></section><?php return ob_get_clean();}
}
MUTQAN_Customer_Portal::init();

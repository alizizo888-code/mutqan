<?php
defined('ABSPATH') || exit;

final class MUTQAN_Analytics {
 public static function init(){add_action('rest_api_init',array(__CLASS__,'rest'));add_action('admin_menu',array(__CLASS__,'menu'),40);}
 private static function can(){return current_user_can('mutqan_manage_operations')||current_user_can('mutqan_manage_finance')||current_user_can('mutqan_view_audit')||current_user_can('mutqan_view_analytics');}
 public static function summary($from,$to){
  global $wpdb;
  $ops=MUTQAN_Operations::table();
  $billing=MUTQAN_Billing::table();
  $users=$wpdb->users;
  $from=sanitize_text_field($from);$to=sanitize_text_field($to);
  $orders=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $ops WHERE created_at BETWEEN %s AND %s",$from.' 00:00:00',$to.' 23:59:59'));
  $completed=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $ops WHERE status IN ('completed','closed') AND created_at BETWEEN %s AND %s",$from.' 00:00:00',$to.' 23:59:59'));
  $cancelled=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $ops WHERE status='cancelled' AND created_at BETWEEN %s AND %s",$from.' 00:00:00',$to.' 23:59:59'));
  $revenue=(float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM $billing WHERE created_at BETWEEN %s AND %s",$from.' 00:00:00',$to.' 23:59:59'));
  $paid=(float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM $billing WHERE payment_status='paid' AND created_at BETWEEN %s AND %s",$from.' 00:00:00',$to.' 23:59:59'));
  $statuses=$wpdb->get_results($wpdb->prepare("SELECT status,COUNT(*) count FROM $ops WHERE created_at BETWEEN %s AND %s GROUP BY status ORDER BY count DESC",$from.' 00:00:00',$to.' 23:59:59'),ARRAY_A);
  $daily=$wpdb->get_results($wpdb->prepare("SELECT DATE(created_at) day,COUNT(*) orders,SUM(status IN ('completed','closed')) completed,SUM(status='cancelled') cancelled FROM $ops WHERE created_at BETWEEN %s AND %s GROUP BY DATE(created_at) ORDER BY day",$from.' 00:00:00',$to.' 23:59:59'),ARRAY_A);
  $tech=$wpdb->get_results("SELECT u.ID id,u.display_name,COUNT(o.id) active_orders FROM $users u INNER JOIN {$wpdb->usermeta} um ON um.user_id=u.ID AND um.meta_key='".$wpdb->prefix."capabilities' LEFT JOIN $ops o ON o.technician_id=u.ID AND o.status IN ('assigned','accepted','en_route','nearby','arrived','working','waiting_customer') WHERE um.meta_value LIKE '%mutqan_technician%' GROUP BY u.ID,u.display_name ORDER BY active_orders DESC LIMIT 50",ARRAY_A);
  $quality=array('count'=>0,'average'=>0);
  if(class_exists('MUTQAN_Quality')){
   $qt=MUTQAN_Quality::table();$q=$wpdb->get_row($wpdb->prepare("SELECT COUNT(*) count,COALESCE(AVG(score),0) average FROM $qt WHERE created_at BETWEEN %s AND %s",$from.' 00:00:00',$to.' 23:59:59'),ARRAY_A);
   if($q)$quality=array('count'=>(int)$q['count'],'average'=>round((float)$q['average'],2));
  }
  return array('from'=>$from,'to'=>$to,'orders'=>$orders,'completed'=>$completed,'cancelled'=>$cancelled,'revenue'=>$revenue,'paid'=>$paid,'completion_rate'=>$orders?round($completed/$orders*100,2):0,'statuses'=>$statuses,'daily'=>$daily,'technicians'=>$tech,'quality'=>$quality);
 }
 public static function rest(){register_rest_route('mutqan/v1','/analytics/summary',array('methods'=>WP_REST_Server::READABLE,'permission_callback'=>function(){return self::can();},'callback'=>function($r){$from=$r->get_param('from')?:gmdate('Y-m-01');$to=$r->get_param('to')?:gmdate('Y-m-d');return rest_ensure_response(self::summary($from,$to));}));}
 public static function menu(){if(!self::can())return;add_submenu_page('mutqan-finance','MUTQAN التحليلات','التحليلات','mutqan_view_analytics','mutqan-analytics',array(__CLASS__,'page'));}
 public static function page(){
  if(!self::can())wp_die('غير مصرح');
  $s=self::summary(gmdate('Y-m-01'),gmdate('Y-m-d'));
  echo '<div class="wrap"><h1>MUTQAN — التحليلات الموحدة</h1><p>الفترة: '.esc_html($s['from']).' إلى '.esc_html($s['to']).'</p>';
  echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin:18px 0">';
  foreach(array('الطلبات'=>$s['orders'],'المكتمل'=>$s['completed'],'الملغى'=>$s['cancelled'],'الإيراد'=>number_format($s['revenue'],2).' SAR','المحصّل'=>number_format($s['paid'],2).' SAR','نسبة الإكمال'=>$s['completion_rate'].'%','متوسط الجودة'=>$s['quality']['average'].'/100') as $k=>$v)echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:16px;min-width:150px"><b>'.esc_html($k).'</b><div style="font-size:24px;margin-top:6px">'.esc_html($v).'</div></div>';
  echo '</div><h2>حالات الطلبات</h2><table class="widefat striped"><thead><tr><th>الحالة</th><th>العدد</th></tr></thead><tbody>';
  foreach($s['statuses'] as $x)echo '<tr><td>'.esc_html($x['status']).'</td><td>'.(int)$x['count'].'</td></tr>';
  echo '</tbody></table><h2>الفنيون — الطلبات النشطة</h2><table class="widefat striped"><thead><tr><th>الفني</th><th>الطلبات النشطة</th></tr></thead><tbody>';
  foreach($s['technicians'] as $x)echo '<tr><td>'.esc_html($x['display_name']).'</td><td>'.(int)$x['active_orders'].'</td></tr>';
  echo '</tbody></table></div>';
 }
}
MUTQAN_Analytics::init();

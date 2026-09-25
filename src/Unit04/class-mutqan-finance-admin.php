<?php
defined('ABSPATH') || exit;

final class MUTQAN_Finance_Admin {
 public static function init(){add_action('admin_menu',array(__CLASS__,'menu'),25);add_action('admin_post_mutqan_finance_action',array(__CLASS__,'action'));add_action('admin_enqueue_scripts',array(__CLASS__,'assets'));}
 public static function menu(){
  if(!current_user_can('mutqan_manage_finance')&&!current_user_can('mutqan_manage_operations')&&!current_user_can('mutqan_view_audit'))return;
  add_menu_page('MUTQAN المالية','المالية','mutqan_manage_finance','mutqan-finance',array(__CLASS__,'page'),'dashicons-money-alt',58);
 }
 public static function assets($hook){if(strpos($hook,'mutqan-finance')===false)return;wp_enqueue_style('dashicons');}
 private static function card($title,$value,$sub=''){echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:18px;min-width:190px;flex:1"><div style="color:#646970;font-size:13px">'.esc_html($title).'</div><div style="font-size:26px;font-weight:700;margin-top:6px">'.esc_html($value).'</div><div style="color:#646970;font-size:12px;margin-top:4px">'.esc_html($sub).'</div></div>';}
 public static function page(){
  global $wpdb;
  $can=current_user_can('mutqan_manage_finance')||current_user_can('mutqan_manage_operations')||current_user_can('mutqan_view_audit');
  if(!$can)wp_die('غير مصرح');
  $revenue=(float)$wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM ".MUTQAN_Billing::table());
  $paid=(float)$wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM ".MUTQAN_Billing::table()." WHERE payment_status='paid'");
  $comm=(float)$wpdb->get_var("SELECT COALESCE(SUM(commission_amount),0) FROM ".MUTQAN_Commissions::ledger());
  $expenses=(float)$wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM ".MUTQAN_Expenses::table()." WHERE status='approved'");
  $invoices=$wpdb->get_results("SELECT * FROM ".MUTQAN_Billing::table()." ORDER BY id DESC LIMIT 20",ARRAY_A);
  $rules=$wpdb->get_results("SELECT * FROM ".MUTQAN_Commissions::table()." ORDER BY id DESC LIMIT 20",ARRAY_A);
  $adv=$wpdb->get_results("SELECT * FROM ".MUTQAN_Advances::table()." ORDER BY id DESC LIMIT 20",ARRAY_A);
  $ex=$wpdb->get_results("SELECT * FROM ".MUTQAN_Expenses::table()." ORDER BY id DESC LIMIT 20",ARRAY_A);
  $sett=$wpdb->get_results("SELECT * FROM ".MUTQAN_Settlements::table()." ORDER BY id DESC LIMIT 20",ARRAY_A);
  ?>
  <div class="wrap"><h1>MUTQAN — الإدارة المالية</h1>
  <?php if(isset($_GET['mutqan_msg']))echo '<div class="notice notice-success is-dismissible"><p>'.esc_html(wp_unslash($_GET['mutqan_msg'])).'</p></div>';?>
  <div style="display:flex;gap:14px;flex-wrap:wrap;margin:18px 0">
  <?php self::card('إجمالي الفواتير',number_format($revenue,2).' SAR','كل الفواتير'); self::card('المحصّل',number_format($paid,2).' SAR','حالة الدفع = مدفوع'); self::card('عمولات الفنيين',number_format($comm,2).' SAR','دفتر العمولات'); self::card('المصروفات المعتمدة',number_format($expenses,2).' SAR','المصروفات التشغيلية'); ?>
  </div>
  <?php if(current_user_can('mutqan_manage_commissions')||current_user_can('mutqan_manage_operations')):?>
  <div class="postbox" style="padding:16px"><h2>إضافة قاعدة عمولة</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><?php wp_nonce_field('mutqan_finance_action');?><input type="hidden" name="action" value="mutqan_finance_action"><input type="hidden" name="op" value="commission_rule">
  <input name="name" placeholder="اسم القاعدة" required> <select name="role"><option value="mutqan_technician">فني</option><option value="mutqan_operations">عمليات</option></select>
  <select name="rate_type"><option value="percent">نسبة %</option><option value="fixed">مبلغ ثابت</option></select>
  <input name="rate" type="number" step="0.01" placeholder="القيمة" required> <input name="min_amount" type="number" step="0.01" placeholder="حد أدنى"> <input name="max_amount" type="number" step="0.01" placeholder="حد أقصى">
  <button class="button button-primary">حفظ قاعدة العمولة</button></form></div><?php endif;?>
  <?php if(current_user_can('mutqan_manage_finance')||current_user_can('mutqan_manage_operations')):?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
  <div class="postbox" style="padding:16px"><h2>سلفة جديدة</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><?php wp_nonce_field('mutqan_finance_action');?><input type="hidden" name="action" value="mutqan_finance_action"><input type="hidden" name="op" value="advance"><input name="user_id" type="number" placeholder="User ID" required> <input name="amount" type="number" step="0.01" placeholder="المبلغ" required> <input name="note" placeholder="ملاحظة"><button class="button button-primary">إصدار السلفة</button></form></div>
  <div class="postbox" style="padding:16px"><h2>مصروف جديد</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><?php wp_nonce_field('mutqan_finance_action');?><input type="hidden" name="action" value="mutqan_finance_action"><input type="hidden" name="op" value="expense"><input name="category" placeholder="الفئة" required> <input name="amount" type="number" step="0.01" placeholder="المبلغ" required> <input name="description" placeholder="الوصف"><button class="button button-primary">تسجيل المصروف</button></form></div>
  </div><?php endif;?>
  <h2>الفواتير الأخيرة</h2><table class="widefat striped"><thead><tr><th>ID</th><th>الطلب</th><th>العميل</th><th>الإجمالي</th><th>الدفع</th><th>الحالة</th></tr></thead><tbody><?php foreach($invoices as $x):?><tr><td><?php echo (int)$x['id'];?></td><td><?php echo (int)$x['order_id'];?></td><td><?php echo (int)$x['customer_id'];?></td><td><?php echo esc_html($x['total'].' '.$x['currency']);?></td><td><?php echo esc_html($x['payment_status']);?></td><td><?php echo esc_html($x['status']);?></td></tr><?php endforeach;?></tbody></table>
  <h2>قواعد العمولات</h2><table class="widefat striped"><thead><tr><th>ID</th><th>الاسم</th><th>الدور</th><th>النوع</th><th>القيمة</th><th>نشطة</th></tr></thead><tbody><?php foreach($rules as $x):?><tr><td><?php echo (int)$x['id'];?></td><td><?php echo esc_html($x['name']);?></td><td><?php echo esc_html($x['role']);?></td><td><?php echo esc_html($x['rate_type']);?></td><td><?php echo esc_html($x['rate']);?></td><td><?php echo $x['active']?'نعم':'لا';?></td></tr><?php endforeach;?></tbody></table>
  <h2>السلف</h2><table class="widefat striped"><thead><tr><th>ID</th><th>المستخدم</th><th>المبلغ</th><th>المتبقي</th><th>الحالة</th></tr></thead><tbody><?php foreach($adv as $x):?><tr><td><?php echo (int)$x['id'];?></td><td><?php echo (int)$x['user_id'];?></td><td><?php echo esc_html($x['amount']);?></td><td><?php echo esc_html($x['remaining']);?></td><td><?php echo esc_html($x['status']);?></td></tr><?php endforeach;?></tbody></table>
  <h2>المصروفات</h2><table class="widefat striped"><thead><tr><th>ID</th><th>الفئة</th><th>المبلغ</th><th>الحالة</th><th>الوصف</th></tr></thead><tbody><?php foreach($ex as $x):?><tr><td><?php echo (int)$x['id'];?></td><td><?php echo esc_html($x['category']);?></td><td><?php echo esc_html($x['amount'].' '.$x['currency']);?></td><td><?php echo esc_html($x['status']);?></td><td><?php echo esc_html($x['description']);?></td></tr><?php endforeach;?></tbody></table>
  <h2>التسويات</h2><table class="widefat striped"><thead><tr><th>ID</th><th>المستخدم</th><th>الفترة</th><th>العمولات</th><th>السلف</th><th>الصافي</th><th>الحالة</th></tr></thead><tbody><?php foreach($sett as $x):?><tr><td><?php echo (int)$x['id'];?></td><td><?php echo (int)$x['user_id'];?></td><td><?php echo esc_html($x['period_from'].' → '.$x['period_to']);?></td><td><?php echo esc_html($x['commissions']);?></td><td><?php echo esc_html($x['advances']);?></td><td><?php echo esc_html($x['net_amount']);?></td><td><?php echo esc_html($x['status']);?></td></tr><?php endforeach;?></tbody></table>
  </div><?php
 }
 public static function action(){
  if(!current_user_can('mutqan_manage_finance')&&!current_user_can('mutqan_manage_operations'))wp_die('غير مصرح');
  check_admin_referer('mutqan_finance_action');global $wpdb;$op=sanitize_key($_POST['op']??'');
  if($op==='commission_rule'&&(current_user_can('mutqan_manage_commissions')||current_user_can('mutqan_manage_operations'))){$wpdb->insert(MUTQAN_Commissions::table(),array('name'=>sanitize_text_field($_POST['name']??''),'role'=>sanitize_key($_POST['role']??'mutqan_technician'),'rate_type'=>sanitize_key($_POST['rate_type']??'percent'),'rate'=>(float)($_POST['rate']??0),'min_amount'=>(float)($_POST['min_amount']??0),'max_amount'=>(float)($_POST['max_amount']??0),'active'=>1,'conditions'=>'{}','created_at'=>current_time('mysql',true),'updated_at'=>current_time('mysql',true)));MUTQAN_Audit::log('commission_rule_created','commission_rule',(int)$wpdb->insert_id,array());}
  elseif($op==='advance'){$uid=absint($_POST['user_id']??0);$a=max(0,(float)($_POST['amount']??0));if($uid&&$a>0)$wpdb->insert(MUTQAN_Advances::table(),array('user_id'=>$uid,'amount'=>$a,'remaining'=>$a,'status'=>'open','note'=>sanitize_textarea_field($_POST['note']??''),'created_at'=>current_time('mysql',true),'updated_at'=>current_time('mysql',true)));}
  elseif($op==='expense'){$a=max(0,(float)($_POST['amount']??0));if($a>0)$wpdb->insert(MUTQAN_Expenses::table(),array('user_id'=>get_current_user_id(),'category'=>sanitize_text_field($_POST['category']??'general'),'amount'=>$a,'currency'=>'SAR','status'=>'pending','description'=>sanitize_textarea_field($_POST['description']??''),'created_at'=>current_time('mysql',true)));}
  wp_safe_redirect(add_query_arg(array('page'=>'mutqan-finance','mutqan_msg'=>'تم تنفيذ العملية'),admin_url('admin.php')));exit;
 }
}
MUTQAN_Finance_Admin::init();

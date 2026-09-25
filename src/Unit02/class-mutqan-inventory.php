<?php
defined('ABSPATH') || exit;
final class MUTQAN_Inventory {
 public static function init(){add_action('rest_api_init',array(__CLASS__,'rest'));}
 public static function table(){global $wpdb;return $wpdb->prefix.'mutqan_inventory';}
 public static function usage_table(){global $wpdb;return $wpdb->prefix.'mutqan_inventory_usage';}
 public static function install(){global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$c=$wpdb->get_charset_collate();
  dbDelta("CREATE TABLE ".self::table()." (id bigint(20) unsigned NOT NULL AUTO_INCREMENT, sku varchar(80) NOT NULL, name varchar(190) NOT NULL, warehouse varchar(190) NOT NULL DEFAULT '', quantity decimal(12,2) NOT NULL DEFAULT 0, reserved decimal(12,2) NOT NULL DEFAULT 0, unit_cost decimal(12,2) NOT NULL DEFAULT 0, reorder_level decimal(12,2) NOT NULL DEFAULT 0, active tinyint(1) NOT NULL DEFAULT 1, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY(id),UNIQUE KEY sku(sku),KEY warehouse(warehouse)) $c;");
  dbDelta("CREATE TABLE ".self::usage_table()." (id bigint(20) unsigned NOT NULL AUTO_INCREMENT, order_id bigint(20) unsigned NOT NULL, technician_id bigint(20) unsigned NOT NULL, vehicle_id bigint(20) unsigned NOT NULL DEFAULT 0, inventory_id bigint(20) unsigned NOT NULL, quantity decimal(12,2) NOT NULL DEFAULT 0, unit_cost decimal(12,2) NOT NULL DEFAULT 0, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY(id),UNIQUE KEY order_item(order_id,inventory_id),KEY technician_id(technician_id),KEY vehicle_id(vehicle_id)) $c;");
 }
 private static function manage(){return current_user_can('mutqan_manage_operations')||current_user_can('mutqan_manage_settings');}
 public static function consume_for_order($order_id,$technician_id,$items){
  global $wpdb; $items=(array)$items; $vehicle_id=0;
  if(class_exists('MUTQAN_Fleet')) $vehicle_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM ".MUTQAN_Fleet::table()." WHERE technician_id=%d AND status='active' ORDER BY id DESC LIMIT 1",$technician_id));
  $normalized=array();
  foreach($items as $item){$iid=is_array($item)?absint($item['inventory_id']??0):absint($item);$qty=is_array($item)?max(0,(float)($item['quantity']??1)):1;if($iid&&$qty>0)$normalized[$iid]=($normalized[$iid]??0)+$qty;}
  $existing_rows=$wpdb->get_results($wpdb->prepare("SELECT inventory_id,quantity FROM ".self::usage_table()." WHERE order_id=%d",$order_id),ARRAY_A);
  $existing=array();foreach($existing_rows as $row)$existing[(int)$row['inventory_id']] = (float)$row['quantity'];
  $deltas=array_unique(array_merge(array_keys($existing),array_keys($normalized)));
  $wpdb->query('START TRANSACTION');
  try{
   foreach($deltas as $iid){
    $qty=(float)($normalized[$iid]??0);$old=(float)($existing[$iid]??0);$delta=$qty-$old;
    if(abs($delta)<0.00001)continue;
    $inv=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::table()." WHERE id=%d FOR UPDATE",$iid),ARRAY_A);
    if(!$inv)throw new Exception('inventory_not_found');
    $stock=$vehicle_id?(float)$wpdb->get_var($wpdb->prepare("SELECT quantity FROM ".MUTQAN_Fleet::stock_table()." WHERE vehicle_id=%d AND inventory_id=%d FOR UPDATE",$vehicle_id,$iid)):0;
    if($delta>0 && $stock<$delta)throw new Exception('vehicle_stock_insufficient');
    $available=(float)$inv['quantity']-(float)$inv['reserved'];if($delta>0&&$available<$delta)throw new Exception('central_stock_insufficient');
    if($delta>0){$wpdb->query($wpdb->prepare("UPDATE ".self::table()." SET quantity=quantity-%f,reserved=GREATEST(reserved-%f,0),updated_at=%s WHERE id=%d",$delta,$delta,current_time('mysql',true),$iid));if($vehicle_id)$wpdb->query($wpdb->prepare("UPDATE ".MUTQAN_Fleet::stock_table()." SET quantity=quantity-%f,updated_at=%s WHERE vehicle_id=%d AND inventory_id=%d",$delta,current_time('mysql',true),$vehicle_id,$iid));}
    else{$back=abs($delta);$wpdb->query($wpdb->prepare("UPDATE ".self::table()." SET quantity=quantity+%f,updated_at=%s WHERE id=%d",$back,current_time('mysql',true),$iid));if($vehicle_id)$wpdb->query($wpdb->prepare("UPDATE ".MUTQAN_Fleet::stock_table()." SET quantity=quantity+%f,updated_at=%s WHERE vehicle_id=%d AND inventory_id=%d",$back,current_time('mysql',true),$vehicle_id,$iid));}
    if($old>0&&$qty>0)$wpdb->update(self::usage_table(),array('quantity'=>$qty,'vehicle_id'=>$vehicle_id,'updated_at'=>current_time('mysql',true)),array('order_id'=>$order_id,'inventory_id'=>$iid));
    elseif($old>0&&$qty<=0)$wpdb->delete(self::usage_table(),array('order_id'=>$order_id,'inventory_id'=>$iid));
    else if($qty>0)$wpdb->insert(self::usage_table(),array('order_id'=>$order_id,'technician_id'=>$technician_id,'vehicle_id'=>$vehicle_id,'inventory_id'=>$iid,'quantity'=>$qty,'unit_cost'=>(float)$inv['unit_cost'],'created_at'=>current_time('mysql',true),'updated_at'=>current_time('mysql',true)));
   }
   $wpdb->query('COMMIT');
  }catch(Exception $e){$wpdb->query('ROLLBACK');$map=array('inventory_not_found'=>array('inventory_not_found','الصنف غير موجود.',404),'vehicle_stock_insufficient'=>array('vehicle_stock_insufficient','كمية القطعة في المركبة غير كافية.',400),'central_stock_insufficient'=>array('central_stock_insufficient','المخزون المركزي غير كافٍ.',400));$x=$map[$e->getMessage()]??array('inventory_transaction_failed','تعذر تحديث المخزون.',500);return new WP_Error($x[0],$x[1],array('status'=>$x[2]));}
  MUTQAN_Audit::log('parts_consumed','order',(int)$order_id,array('technician_id'=>$technician_id,'vehicle_id'=>$vehicle_id,'items'=>$normalized));
  return true;
 }
 public static function rest(){
  register_rest_route('mutqan/v1','/inventory',array('methods'=>WP_REST_Server::READABLE,'permission_callback'=>function(){return self::manage();},'callback'=>function(){global $wpdb;return rest_ensure_response($wpdb->get_results("SELECT *,GREATEST(quantity-reserved,0) available FROM ".self::table()." ORDER BY name ASC",ARRAY_A));}));
  register_rest_route('mutqan/v1','/inventory',array('methods'=>WP_REST_Server::CREATABLE,'permission_callback'=>function(){return self::manage();},'callback'=>function($r){global $wpdb;$p=$r->get_json_params();$d=array('sku'=>sanitize_text_field($p['sku']??''),'name'=>sanitize_text_field($p['name']??''),'warehouse'=>sanitize_text_field($p['warehouse']??''),'quantity'=>max(0,(float)($p['quantity']??0)),'reserved'=>max(0,(float)($p['reserved']??0)),'unit_cost'=>max(0,(float)($p['unit_cost']??0)),'reorder_level'=>max(0,(float)($p['reorder_level']??0)),'active'=>empty($p['active'])?0:1,'created_at'=>current_time('mysql',true),'updated_at'=>current_time('mysql',true));if($d['sku']===''||$d['name']==='')return new WP_Error('invalid_item','SKU and name are required.',array('status'=>400));$wpdb->replace(self::table(),$d);$id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM ".self::table()." WHERE sku=%s",$d['sku']));MUTQAN_Audit::log('inventory_item_saved','inventory',$id,array('sku'=>$d['sku']));return rest_ensure_response(array('id'=>$id)+$d);})); 
  register_rest_route('mutqan/v1','/inventory/alerts',array('methods'=>'GET','permission_callback'=>function(){return self::manage();},'callback'=>function(){global $wpdb;return rest_ensure_response($wpdb->get_results("SELECT *,GREATEST(quantity-reserved,0) available FROM ".self::table()." WHERE active=1 AND quantity-reserved<=reorder_level ORDER BY (quantity-reserved) ASC",ARRAY_A));}));
  register_rest_route('mutqan/v1','/orders/(?P<id>\d+)/parts',array('methods'=>WP_REST_Server::EDITABLE,'permission_callback'=>function($r){return MUTQAN_Orders::can_view((int)$r['id'])&&(current_user_can('mutqan_manage_operations')||current_user_can('mutqan_edit_orders'));},'callback'=>function($r){global $wpdb;$id=(int)$r['id'];$items=(array)($r->get_json_params()['items']??array());foreach($items as $item){$iid=absint($item['inventory_id']??0);$qty=max(0,(float)($item['quantity']??0));if(!$iid||!$qty)continue;$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::table()." WHERE id=%d",$iid),ARRAY_A);if(!$row||((float)$row['quantity']-(float)$row['reserved'])<$qty)return new WP_Error('insufficient_stock','Insufficient stock.',array('status'=>400));$wpdb->update(self::table(),array('reserved'=>(float)$row['reserved']+$qty,'updated_at'=>current_time('mysql',true)),array('id'=>$iid));}MUTQAN_Audit::log('order_parts_reserved','order',$id,array('items'=>$items));return rest_ensure_response(array('order_id'=>$id,'reserved'=>true);}));
  register_rest_route('mutqan/v1','/orders/(?P<id>\d+)/parts-used',array('methods'=>'GET','permission_callback'=>function($r){return MUTQAN_Orders::can_view((int)$r['id']);},'callback'=>function($r){global $wpdb;return rest_ensure_response($wpdb->get_results($wpdb->prepare("SELECT u.*,i.sku,i.name FROM ".self::usage_table()." u JOIN ".self::table()." i ON i.id=u.inventory_id WHERE u.order_id=%d",(int)$r['id']),ARRAY_A));}));
 }
}
MUTQAN_Inventory::init();
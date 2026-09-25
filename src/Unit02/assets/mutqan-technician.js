(function(){
function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
var S=window.MUTQAN_TECH||{}, root=S.root||'/wp-json/mutqan/v1';
function api(path,opt){opt=opt||{};opt.credentials='same-origin';opt.headers=Object.assign({'X-WP-Nonce':S.nonce||'','Content-Type':'application/json'},opt.headers||{});return fetch(root+path,opt).then(function(r){return r.json().then(function(x){if(!r.ok)throw new Error(x.message||'حدث خطأ');return x;});});}
function statusLabel(s){return {assigned:'مُسند',accepted:'مقبول',en_route:'في الطريق',nearby:'قريب',arrived:'وصل',working:'يعمل',waiting_customer:'بانتظار العميل'}[s]||s;}
function actionFor(o){
 if(o.status==='assigned')return '<button class="primary" data-order="'+o.id+'" data-status="accepted">قبول الطلب</button><button data-order="'+o.id+'" data-status="declined">رفض</button>';
 if(o.status==='accepted')return '<button class="primary" data-order="'+o.id+'" data-status="en_route">بدء التحرك</button>';
 if(o.status==='en_route'||o.status==='nearby')return '<button class="primary" data-order="'+o.id+'" data-status="arrived">تأكيد الوصول</button>';
 if(o.status==='arrived'||o.status==='waiting_customer')return '<button class="primary" data-order="'+o.id+'" data-status="working">بدء العمل</button>';
 if(o.status==='working')return '<button class="primary" data-report="'+o.id+'">تقرير الخدمة</button>';
 return '';
}
function load(){
 var box=document.querySelector('[data-tech-orders]'); if(!box)return;
 api('/technician/orders').then(function(rows){
  box.innerHTML=rows.length?rows.map(function(o){var p=o.payload||{};return '<article class="mq-tech-card"><h3>#'+esc(o.id)+' · '+esc(p.service_name||p.service||o.type)+'</h3><div class="mq-tech-meta">الحالة: '+esc(statusLabel(o.status))+' · الأولوية: '+esc(o.priority)+'</div><div class="mq-tech-meta">الموقع: '+esc(p.location_label||'غير محدد')+'</div><div class="mq-tech-meta">التفاصيل: '+esc(p.details||'لا توجد تفاصيل')+'</div><div class="mq-tech-actions">'+actionFor(o)+'<button data-detail="'+o.id+'">التفاصيل</button></div></article>';}).join(''):'<div class="mq-tech-empty">لا توجد مهام حالية.</div>';
 }).catch(function(e){box.innerHTML='<div class="mq-tech-danger">'+esc(e.message)+'</div>';});
 api('/technician/me').then(function(x){var s=document.querySelector('[data-tech-state]');if(s)s.textContent='الحالة: '+esc(x.status)+' · المهام '+esc(x.active_orders)+' / '+esc(x.capacity);}).catch(function(){});
}
function reportForm(id){var d=document.querySelector('[data-tech-detail]');d.hidden=false;d.innerHTML='<h3>تقرير الخدمة #'+esc(id)+'</h3><p><textarea data-report-diagnosis placeholder="التشخيص" style="width:100%;min-height:70px"></textarea></p><p><textarea data-report-work placeholder="تقرير العمل" style="width:100%;min-height:90px"></textarea></p><p><label><input type="checkbox" data-check="1"> فحص أولي</label> <label><input type="checkbox" data-check="2"> تنفيذ الخدمة</label> <label><input type="checkbox" data-check="3"> اختبار التشغيل</label></p><p><input data-report-parts placeholder="أرقام قطع الغيار مفصولة بفاصلة" style="width:100%"></p><button class="primary" data-save-report="'+id+'">حفظ التقرير</button></p>';}
var gpsWatch=null,gpsLastSent=0,gpsOrder=0;
function activeOrderId(){var x=document.querySelector('.mq-tech-card [data-status="en_route"]');return x?parseInt(x.getAttribute('data-order'),10):0;}
function startGPS(){if(!navigator.geolocation||gpsWatch!==null)return;gpsWatch=navigator.geolocation.watchPosition(function(p){
 var now=Date.now(); if(now-gpsLastSent<10000)return; gpsLastSent=now;
 api('/gps/location',{method:'POST',body:JSON.stringify({lat:p.coords.latitude,lng:p.coords.longitude,accuracy:p.coords.accuracy,order_id:activeOrderId()||0})}).catch(function(){});
 },function(){},{enableHighAccuracy:true,maximumAge:5000,timeout:15000});}
function stopGPS(){if(gpsWatch!==null&&navigator.geolocation){navigator.geolocation.clearWatch(gpsWatch);gpsWatch=null;}}

function setStatus(s){
 var body={status:s}; if(s==='available'||s==='busy')startGPS();
 if(s==='en_route')startGPS(); if(navigator.geolocation)navigator.geolocation.getCurrentPosition(function(pos){body.lat=pos.coords.latitude;body.lng=pos.coords.longitude;api('/technicians/me/status',{method:'POST',body:JSON.stringify(body)}).then(load).catch(alert);},function(){api('/technicians/me/status',{method:'POST',body:JSON.stringify(body)}).then(load).catch(alert);});
 else api('/technicians/me/status',{method:'POST',body:JSON.stringify(body)}).then(load).catch(alert);
}
document.addEventListener('click',function(e){
 var rf=e.target.closest('[data-report]');if(rf){reportForm(rf.dataset.report);return;} var sr=e.target.closest('[data-save-report]');if(sr){var checks=[];document.querySelectorAll('[data-check]:checked').forEach(function(x){checks.push(x.dataset.check);});var parts=(document.querySelector('[data-report-parts]').value||'').split(',').map(function(x){return parseInt(x.trim(),10);}).filter(Boolean);api('/orders/'+sr.dataset.saveReport+'/execution',{method:'PUT',body:JSON.stringify({diagnosis:document.querySelector('[data-report-diagnosis]').value,work_report:document.querySelector('[data-report-work]').value,checklist:checks,parts_used:parts})}).then(function(){alert('تم حفظ التقرير. ينتظر تأكيد العميل لإغلاق الطلب.');load();}).catch(function(x){alert(x.message);});return;} var a=e.target.closest('[data-order][data-status]'); if(a){a.disabled=true;api('/orders/'+a.dataset.order+'/status',{method:'POST',body:JSON.stringify({status:a.dataset.status})}).then(load).catch(function(x){a.disabled=false;alert(x.message);});return;}
 var d=e.target.closest('[data-detail]');if(d){api('/orders/'+d.dataset.detail).then(function(o){var x=document.querySelector('[data-tech-detail]');x.hidden=false;x.innerHTML='<strong>الطلب #'+esc(o.id)+'</strong><p>'+esc((o.payload||{}).details||'لا توجد تفاصيل')+'</p><a target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(o.lat+','+o.lng)+'">فتح الموقع على Google Maps</a>';}).catch(function(x){alert(x.message);});}
 var st=e.target.closest('[data-tech-status]');if(st)setStatus(st.dataset.techStatus);
 var ref=e.target.closest('[data-tech-refresh]');if(ref)load();
});
document.addEventListener('DOMContentLoaded',function(){if(document.querySelector('.mq-tech-portal')){load();setInterval(load,10000);}});
})();
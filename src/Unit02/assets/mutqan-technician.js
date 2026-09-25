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
 if(o.status==='working')return '<button class="primary" data-order="'+o.id+'" data-status="completed">إغلاق الخدمة</button>';
 return '';
}
function load(){
 var box=document.querySelector('[data-tech-orders]'); if(!box)return;
 api('/technician/orders').then(function(rows){
  box.innerHTML=rows.length?rows.map(function(o){var p=o.payload||{};return '<article class="mq-tech-card"><h3>#'+esc(o.id)+' · '+esc(p.service_name||p.service||o.type)+'</h3><div class="mq-tech-meta">الحالة: '+esc(statusLabel(o.status))+' · الأولوية: '+esc(o.priority)+'</div><div class="mq-tech-meta">الموقع: '+esc(p.location_label||'غير محدد')+'</div><div class="mq-tech-meta">التفاصيل: '+esc(p.details||'لا توجد تفاصيل')+'</div><div class="mq-tech-actions">'+actionFor(o)+'<button data-detail="'+o.id+'">التفاصيل</button></div></article>';}).join(''):'<div class="mq-tech-empty">لا توجد مهام حالية.</div>';
 }).catch(function(e){box.innerHTML='<div class="mq-tech-danger">'+esc(e.message)+'</div>';});
 api('/technician/me').then(function(x){var s=document.querySelector('[data-tech-state]');if(s)s.textContent='الحالة: '+esc(x.status)+' · المهام '+esc(x.active_orders)+' / '+esc(x.capacity);}).catch(function(){});
}
function setStatus(s){
 var body={status:s};
 if(navigator.geolocation)navigator.geolocation.getCurrentPosition(function(pos){body.lat=pos.coords.latitude;body.lng=pos.coords.longitude;api('/technicians/me/status',{method:'POST',body:JSON.stringify(body)}).then(load).catch(alert);},function(){api('/technicians/me/status',{method:'POST',body:JSON.stringify(body)}).then(load).catch(alert);});
 else api('/technicians/me/status',{method:'POST',body:JSON.stringify(body)}).then(load).catch(alert);
}
document.addEventListener('click',function(e){
 var a=e.target.closest('[data-order][data-status]'); if(a){a.disabled=true;api('/orders/'+a.dataset.order+'/status',{method:'POST',body:JSON.stringify({status:a.dataset.status})}).then(load).catch(function(x){a.disabled=false;alert(x.message);});return;}
 var d=e.target.closest('[data-detail]');if(d){api('/orders/'+d.dataset.detail).then(function(o){var x=document.querySelector('[data-tech-detail]');x.hidden=false;x.innerHTML='<strong>الطلب #'+esc(o.id)+'</strong><p>'+esc((o.payload||{}).details||'لا توجد تفاصيل')+'</p><a target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(o.lat+','+o.lng)+'">فتح الموقع على Google Maps</a>';}).catch(function(x){alert(x.message);});}
 var st=e.target.closest('[data-tech-status]');if(st)setStatus(st.dataset.techStatus);
 var ref=e.target.closest('[data-tech-refresh]');if(ref)load();
});
document.addEventListener('DOMContentLoaded',function(){if(document.querySelector('.mq-tech-portal')){load();setInterval(load,10000);}});
})();
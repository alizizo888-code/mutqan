(function(){
function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
var map=null, markers={}, orderMarkers={}, trackLines={};
function initMap(root){
 if(!window.L||map)return;
 map=L.map(root.querySelector('[data-radar-map]')).setView([21.4225,39.8262],11);
 L.tileLayer((window.MUTQAN_RADAR||{}).tileUrl,{attribution:(window.MUTQAN_RADAR||{}).tileAttribution,maxZoom:19}).addTo(map);
}
function markerIcon(label,kind){
 return L.divIcon({className:'mq-map-marker mq-'+kind,html:'<span>'+esc(label)+'</span>',iconSize:[36,28],iconAnchor:[18,14]});
}
function upsertMarkers(data){
 var bounds=[];
 (data.technicians||[]).forEach(function(x){
   if(!x.lat||!x.lng)return;
   var m=markers[x.id];
   if(!m)m=L.marker([x.lat,x.lng],{icon:markerIcon('فني','tech')}).addTo(map);
   m.setLatLng([x.lat,x.lng]);m.bindPopup('<strong>'+esc(x.name)+'</strong><br>'+esc(x.status)+'<br>GPS: '+esc(x.lat)+', '+esc(x.lng));
   bounds.push([x.lat,x.lng]);
 });
 (data.orders||[]).forEach(function(x){
   if(!x.lat||!x.lng)return;
   var m=orderMarkers[x.id];
   if(!m)m=L.marker([x.lat,x.lng],{icon:markerIcon('#'+x.id,'order')}).addTo(map);
   m.setLatLng([x.lat,x.lng]);m.bindPopup('<strong>طلب #'+esc(x.id)+'</strong><br>'+esc(x.status)+' · '+esc(x.priority));
   bounds.push([x.lat,x.lng]);
 });
 if(bounds.length&&!map._userMoved)map.fitBounds(bounds,{padding:[30,30],maxZoom:13});
}
function render(root,data){
 var s=data.summary||{}, t=s.technicians||{}, o=s.orders||{};
 root.querySelector('[data-radar-summary]').innerHTML='<div class="mq-radar-stat"><b>'+esc(t.available||0)+'</b><small>فنيون متاحون</small></div><div class="mq-radar-stat"><b>'+esc(t.busy||0)+'</b><small>فنيون مشغولون</small></div><div class="mq-radar-stat"><b>'+esc((o.new||0)+(o.pending_assignment||0))+'</b><small>طلبات تنتظر التوزيع</small></div><div class="mq-radar-stat"><b>'+esc(o.urgent||0)+'</b><small>طلبات عاجلة</small></div>';
 root.querySelector('[data-radar-techs]').innerHTML=(data.technicians||[]).map(function(x){return '<div class="mq-radar-item"><strong>'+esc(x.name)+'</strong><div class="mq-radar-meta">'+esc(x.status)+' · السعة '+esc(x.active_orders)+' / '+esc(x.capacity)+'</div><span class="mq-radar-pill">'+(x.lat&&x.lng?'GPS متاح':'GPS غير متاح')+'</span></div>';}).join('')||'<div class="mq-radar-meta">لا يوجد فنيون ظاهرون.</div>';
 root.querySelector('[data-radar-orders]').innerHTML=(data.orders||[]).map(function(x){return '<div class="mq-radar-item"><strong>#'+esc(x.id)+' · '+esc(x.type)+'</strong><div class="mq-radar-meta">'+esc(x.status)+' · '+esc(x.priority)+'</div><span class="mq-radar-pill">'+(x.technician_id?'معيّن':'غير معيّن')+'</span>'+(x.technician_id?'':' <button type="button" class="mq-radar-assign" data-order-auto="'+esc(x.id)+'">إسناد تلقائي</button>')+'</div>';}).join('')||'<div class="mq-radar-meta">لا توجد طلبات مفتوحة.</div>';
 initMap(root);upsertMarkers(data);
}
function load(root){
 fetch((window.MUTQAN_RADAR||{}).endpoint,{credentials:'same-origin',headers:{'X-WP-Nonce':(window.MUTQAN_RADAR||{}).nonce||''}})
 .then(function(r){return r.json();}).then(function(data){if(data&&data.summary)render(root,data);}).catch(function(){});
}
document.addEventListener('click',function(e){
 var b=e.target.closest('[data-order-auto]');
 if(!b)return;
 var id=b.getAttribute('data-order-auto');b.disabled=true;
 fetch('/wp-json/mutqan/v1/radar/order/'+encodeURIComponent(id)+'/auto-assign',{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':(window.MUTQAN_RADAR||{}).nonce||'','Content-Type':'application/json'}})
 .then(function(r){return r.json().then(function(x){if(!r.ok)throw new Error(x.message||'تعذر الإسناد');return x;});})
 .then(function(){var root=b.closest('.mq-radar');if(root)load(root);})
 .catch(function(err){b.disabled=false;b.textContent=err.message;});
});
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('.mq-radar').forEach(function(root){
   var mapBox=root.querySelector('[data-radar-map]');
   if(mapBox)mapBox.addEventListener('mousedown',function(){if(map)map._userMoved=true;});
   var btn=root.querySelector('[data-radar-refresh]');if(btn)btn.addEventListener('click',function(){load(root);});
   load(root);window.setInterval(function(){load(root);},(window.MUTQAN_RADAR||{}).refreshMs||10000);
 });
});
})();

(function(){
function api(path,opts){opts=opts||{};opts.credentials='same-origin';opts.headers=Object.assign({'Content-Type':'application/json','X-WP-Nonce':(window.mqCustomer&&mqCustomer.nonce)||''},opts.headers||{});return fetch('/wp-json/mutqan/v1'+path,opts).then(function(r){return r.json().then(function(x){if(!r.ok)throw new Error(x.message||'حدث خطأ');return x;});});}
var trackers={};
function trackOrder(id,box){
 if(!window.L)return;
 var mapBox=box.querySelector('[data-track-map]');
 if(!mapBox)return;
 var map=L.map(mapBox).setView([21.4225,39.8262],12);
 L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap contributors',maxZoom:19}).addTo(map);
 var techMarker=null, customerMarker=null, line=null;
 function refresh(){
  api('/orders/'+id+'/technician-location').then(function(x){
   if(!x.available)return;
   var pts=[];
   if(x.lat&&x.lng){if(!techMarker)techMarker=L.marker([x.lat,x.lng]).addTo(map);techMarker.setLatLng([x.lat,x.lng]).bindPopup('موقع الفني');pts.push([x.lat,x.lng]);}
   if(x.order_lat&&x.order_lng){if(!customerMarker)customerMarker=L.marker([x.order_lat,x.order_lng]).addTo(map);customerMarker.setLatLng([x.order_lat,x.order_lng]).bindPopup('موقع الخدمة');pts.push([x.order_lat,x.order_lng]);}
   if(pts.length===2){if(line)line.remove();line=L.polyline(pts).addTo(map);map.fitBounds(pts,{padding:[30,30],maxZoom:15});}
   box.querySelector('[data-track-info]').textContent=(x.distance_km!=null?'المسافة التقريبية: '+x.distance_km+' كم · ':'')+(x.eta_minutes!=null?'الوقت التقديري: '+x.eta_minutes+' دقيقة · ':'')+'آخر تحديث: '+(x.recorded_at||'—');
  }).catch(function(){});
 }
 refresh();trackers[id]=window.setInterval(refresh,10000);
}
function loadCustomerOrders(){
 if(!window.mqCustomer||!mqCustomer.loggedIn)return;
 var box=document.querySelector('[data-mq-customer-orders]');if(!box)return;
 api('/customer/orders').then(function(rows){
  box.innerHTML=rows.length?'<h3>طلباتي</h3>'+rows.map(function(o){
   var p=o.payload||{}, active=['assigned','accepted','en_route','nearby','arrived','working','waiting_customer'].indexOf(o.status)>=0&&o.technician_id;
   var confirm=(o.status==='working'&&o.technician_id)?' <button data-confirm-order="'+o.id+'">تأكيد إتمام الخدمة</button>':'';
   var tracker=active?'<div class="mq-order-tracker" data-track-box="'+o.id+'"><div data-track-info>جاري تحديد موقع الفني...</div><div data-track-map></div></div>':'';
   return '<div class="mq-customer-order"><b>#'+o.id+'</b> · '+(p.service_name||o.type)+'<br><small>الحالة: '+o.status+' · السعر: '+(p.quoted_price||0)+' SAR</small><br>'+confirm+tracker+'</div>';
  }).join(''):'<p>لا توجد طلبات سابقة.</p>';
  rows.forEach(function(o){if(['assigned','accepted','en_route','nearby','arrived','working','waiting_customer'].indexOf(o.status)>=0&&o.technician_id){var b=document.querySelector('[data-track-box="'+o.id+'"]');if(b)trackOrder(o.id,b);}});
 }).catch(function(){});
}
document.addEventListener('click',function(e){var b=e.target.closest('[data-confirm-order]');if(!b)return;b.disabled=true;api('/customer/orders/'+b.dataset.confirmOrder+'/confirm',{method:'POST',body:JSON.stringify({confirmation:'تم تأكيد إتمام الخدمة من العميل'})}).then(loadCustomerOrders).catch(function(x){b.disabled=false;alert(x.message);});});
document.addEventListener('DOMContentLoaded',function(){var f=document.querySelector('[data-mq-order-form]');if(!f)return;var s=f.querySelector('[data-mq-services]'),out=f.querySelector('[data-mq-order-result]');api('/customer/services').then(function(a){s.innerHTML='<option value="">اختر الخدمة</option>';a.forEach(function(x){var o=document.createElement('option');o.value=x.id;o.textContent=x.name+' — '+x.price+' SAR';s.appendChild(o);});}).catch(function(){s.innerHTML='<option value="">تعذر تحميل الخدمات</option>';});var b=f.querySelector('[data-mq-location]');b.onclick=function(){if(!navigator.geolocation){out.textContent='المتصفح لا يدعم تحديد الموقع';return;}navigator.geolocation.getCurrentPosition(function(p){f.querySelector('[name=lat]').value=p.coords.latitude;f.querySelector('[name=lng]').value=p.coords.longitude;b.textContent='تم تحديد الموقع ✓';},function(){out.textContent='يرجى السماح بالوصول إلى الموقع';},{enableHighAccuracy:true,timeout:10000,maximumAge:30000});};f.onsubmit=function(e){e.preventDefault();var d={};new FormData(f).forEach(function(v,k){d[k]=v;});var path=window.mqCustomer&&mqCustomer.loggedIn?'/customer/orders':'/public/customer-order';api(path,{method:'POST',body:JSON.stringify(d)}).then(function(x){out.innerHTML='<strong>تم إنشاء الطلب #'+x.id+' ✓</strong><br>السعر التقديري: '+x.quoted_price+' SAR';loadCustomerOrders();}).catch(function(e){out.textContent=e.message;});};loadCustomerOrders();});
})();
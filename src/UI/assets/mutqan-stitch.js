document.addEventListener('DOMContentLoaded',function(){
function esc(v){return String(v).replace(/[&<>"']/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});}
function refNote(){return '<div class="mq-reference-note" style="margin:10px 0;padding:8px 10px;border-radius:9px;background:#fff9ed;color:#795b24;font-size:9px;border:1px solid #f5e1b7">بيانات مرجعية مستخرجة من شاشات STITCH المرفقة — لا تُكتب في قاعدة بيانات MUTQAN.</div>';}
var orders=[
['8341','عاجل • سكني','سالم الحربي','جدة - حي الروضة، شارع الكيال','غسيل مكيفين سبليت + شحن فريون R410','أحمد الشهري','في الموقع / قيد التنفيذ','395.50 ر.س','مدفوع (Apple Pay)'],
['8342','عطل معقد','م. فيصل الغامدي','جدة - حي المحمدية','عطل ضاغط تكييف مركزي (Compressor Trip)','مصطفى علي','في الطريق (12 دقيقة)','580.00 ر.س','بانتظار التأكيد الفني'],
['8343','عقد شركات (B2B)','شركة دار الأفق للاستشارات','الرياض - طريق الملك فهد','صيانة وقائية دورية شاملة (8 وحدات مكتبية)','فريق المساندة 3','مجدول اليوم 02:00 م','1,450.00 ر.س','فاتورة ضريبية مؤجلة'],
['8340','مكتمل للتو','د. ناصر القحطاني','جدة - حي الشاطئ','علاج تسريب مياه داخلي وتنظيف المبخر','عمر الزهراني','مكتمل - بانتظار الاعتماد','260.00 ر.س','مسدد (مدى)']
];
var techs=[
['أحمد الشهري','#TECH-1048','صيانة وتأسيس تكييف سبليت ومركزي معتمد R410A','في الموقع: طلب #8341','342','4.98','98.4%','0.3%','4,820.00 ر.س'],
['مصطفى علي','#TECH-1092','كهرباء ضواغط وتبريد مركزي وتشخيص الكمبروسر','في الطريق: طلب #8342','215','4.86','95.1%','0.8%','3,450.00 ر.س'],
['فريق المساندة 3','B2B','صيانة وقائية لعقود الشركات وتنظيف مجاري الهواء','مجدول اليوم 02:00 م','520','4.79','93.8%','1.2%','8,900.00 ر.س'],
['عمر الزهراني','#TECH-1105','كشف تهريب مياه ونتروجين وضغط فريون','متاح وجاهز للإسناد','189','4.94','97.2%','0.4%','2,880.00 ر.س'],
['فهد الدوسري','#TECH-1140','صيانة مكيفات سبليت وفلاتر','متاح وجاهز للإسناد','44','4.75','92.0%','1.5%','1,120.00 ر.س']
];
var inv=[
['كابستور تشغيل أصلي مروحة وضاغط (45+5 uF)','PRT-AC-8841','Shizuki / تايوان أصلي','Rack-A4 • Bin-12','245','65.00 ر.س','28.00 ر.س','صُرف 18 حبة','ضمان ذهبي 12 شهر'],
['اسطوانة غاز فريون R410A أمريكي نقي (11.3 كجم)','PRT-GAS-410A','Honeywell / أمريكي 99.9%','Zone-Gas • Bay-03','38','380.00 ر.س','210.00 ر.س','سحب 12 اسطوانة','ضمان نقاء الغاز 100%'],
['كمبروسر روتاري 2 طن R410A (24,000 BTU)','PRT-CMP-24LG','LG Rotary QK222P','Rack-C1 • Floor-01','4','850.00 ر.س','580.00 ر.س','محجوز 2 للطلب #8342','ضمان ذهبي سنتين'],
['موتور مروحة مكثف خارجية نحاس نقي (50 واط)','PRT-MTR-50W','Gree Original','Rack-B2 • Bin-08','19','175.00 ر.س','92.00 ر.س','جاهز للصرف','ضمان ذهبي سنة'],
['لوحة تحكم إلكترونية ذكية عالمية إنفيرتر','PRT-PCB-990','QD-U12A معتمدة','Rack-E2 • Anti-Static 04','14','290.00 ر.س','140.00 ر.س','صُرفت 3 لوحات','ضمان ذهبي 6 أشهر']
];
var fleet=[
['OXY-VAN-014','فان صيانة 14','أ ب ج 4821','أحمد الشهري','في المهمة #8341','80%','عدة تكييف + مضخة غسيل + اسطوانتا R410A'],
['OXY-VAN-021','فان صيانة 21','د هـ و 1934','مصطفى علي','في الطريق #8342','65%','عدة كهرباء ضواغط ولوحات تحكم'],
['OXY-VAN-008','فان B2B 08','ر س ت 7752','فريق المساندة 3','مجدول 02:00 م','92%','مخزون عقود الشركات وأدوات القياس']
];
function table(headers,rows,renderer){return '<div class="mq-table-wrap"><table><thead><tr>'+headers.map(esc).map(function(h){return '<th>'+h+'</th>';}).join('')+'</tr></thead><tbody>'+rows.map(function(r){return '<tr data-mq-row>'+renderer(r)+'</tr>';}).join('')+'</tbody></table></div>'+refNote();}
function cell(v,b){return '<td>'+(b?'<b>'+esc(v)+'</b>':esc(v))+'</td>';}
document.querySelectorAll('.mq-stitch').forEach(function(app){
 var view=app.getAttribute('data-view');
 var empty=app.querySelector('.mq-empty');
 if(empty){
  if(view==='overview'||view==='operations'){
   empty.outerHTML=table(['الطلب','العميل والموقع','الخدمة والتشخيص','الفني','الحالة','القيمة والفوترة'],orders,function(r){
    return cell('#'+r[0],true)+cell(r[2]+' — '+r[3])+cell(r[4])+cell(r[5])+ '<td><span class="mq-status">'+esc(r[6])+'</span></td><td><b>'+esc(r[7])+'</b><small>'+esc(r[8])+'</small></td>';
   });
  } else if(view==='technicians'){
   empty.outerHTML=table(['الفني / مزود الخدمة','التخصص','الحالة','المهام','الرضا','SLA','المطالبات','المستحقات'],techs,function(r){
    return cell(r[0]+' '+r[1],true)+cell(r[2])+'<td><span class="mq-status good">'+esc(r[3])+'</span></td>'+cell(r[4])+cell(r[5])+cell(r[6])+cell(r[7])+cell(r[8]);
   });
  } else if(view==='inventory'){
   empty.outerHTML=table(['الصنف / SKU','التصنيف','الرف','الكمية','السعر / الكلفة','الحركة','الضمان'],inv,function(r){
    return cell(r[0]+' — '+r[1],true)+cell(r[2])+cell(r[3])+cell(r[4])+cell(r[5]+' / '+r[6])+cell(r[7])+cell(r[8]);
   });
  } else if(view==='fleet'){
   empty.outerHTML=table(['المركبة','اللوحة','الفني','الحالة','السعة','العهدة'],fleet,function(r){
    return cell(r[1]+' — '+r[0],true)+cell(r[2])+cell(r[3])+ '<td><span class="mq-status good">'+esc(r[4])+'</span></td>'+cell(r[5])+cell(r[6]);
   });
  }
 }
 if(input=app.querySelector('[data-mq-filter]')) input.addEventListener('input',function(){var q=input.value.trim().toLowerCase();app.querySelectorAll('[data-mq-row]').forEach(function(row){row.style.display=!q||row.textContent.toLowerCase().indexOf(q)!==-1?'':'none';});});
 app.querySelectorAll('[data-mq-refresh]').forEach(function(btn){btn.addEventListener('click',function(){window.location.reload();});});
});
});
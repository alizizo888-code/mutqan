(function(){
function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
function uploadFile(cfg,file){var fd=new FormData();fd.append('file',file);return fetch(cfg.base+'/upload',{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':cfg.nonce||''},body:fd}).then(function(r){return r.json().then(function(x){if(!r.ok)throw new Error(x.message||'تعذر الرفع');return x;});});}
function load(root){
 var cfg=window.MUTQAN_CHAT||{}; fetch(cfg.endpoint,{credentials:'same-origin',headers:{'X-WP-Nonce':cfg.nonce||''}}).then(function(r){return r.json();}).then(function(rows){
   if(!Array.isArray(rows))return; var box=root.querySelector('[data-chat-messages]');
   box.innerHTML=rows.map(function(m){return '<div class="mq-msg '+(Number(m.sender_id)===Number(cfg.userId)?'mine':'')+'"><div>'+esc(m.body||('['+m.type+']'))+'</div><small>'+esc(m.created_at)+'</small></div>';}).join('');
   box.scrollTop=box.scrollHeight;
   rows.filter(function(m){return Number(m.recipient_id)===Number(cfg.userId)&&!Number(m.is_read);}).forEach(function(m){fetch(cfg.base+'/messages/'+m.id+'/read',{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':cfg.nonce||'','Content-Type':'application/json'}});});
 }).catch(function(){});
}
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('.mq-chat').forEach(function(root){
   var form=root.querySelector('[data-chat-form]');
   var fi=root.querySelector('[data-chat-file]');if(fi)fi.addEventListener('change',function(){var f=fi.files[0];if(!f)return;var cfg=window.MUTQAN_CHAT||{};uploadFile(cfg,f).then(function(){fi.value='';load(root);}).catch(function(e){alert(e.message);});});var wb=root.querySelector('[data-wa]');if(wb)wb.addEventListener('click',function(){var ph=(root.querySelector('[data-wa-phone]')||{}).value||'';ph=ph.replace(/\D/g,'');if(ph)window.open('https://wa.me/'+ph,'_blank','noopener');});if(form)form.addEventListener('submit',function(e){e.preventDefault();var cfg=window.MUTQAN_CHAT||{},input=form.querySelector('input[name=body]'),body=input.value.trim();if(!body)return;fetch(cfg.base,{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':cfg.nonce||'','Content-Type':'application/json'},body:JSON.stringify({type:'text',body:body})}).then(function(){input.value='';load(root);});});
   load(root);window.setInterval(function(){load(root);},5000);
 });
});
})();
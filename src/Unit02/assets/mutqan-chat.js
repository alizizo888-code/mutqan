(function(){
function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
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
   if(form)form.addEventListener('submit',function(e){e.preventDefault();var cfg=window.MUTQAN_CHAT||{},input=form.querySelector('input[name=body]'),body=input.value.trim();if(!body)return;fetch(cfg.base,{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':cfg.nonce||'','Content-Type':'application/json'},body:JSON.stringify({type:'text',body:body})}).then(function(){input.value='';load(root);});});
   load(root);window.setInterval(function(){load(root);},5000);
 });
});
})();
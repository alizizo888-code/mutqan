document.addEventListener('DOMContentLoaded',function(){
  'use strict';
  var root=document.querySelector('.mq-stitch');
  if(!root)return;
  var menu=root.querySelector('[data-mq-menu]');
  var backdrop=root.querySelector('[data-mq-backdrop]');
  function closeMenu(){root.classList.remove('mq-menu-open');}
  if(menu)menu.addEventListener('click',function(){root.classList.toggle('mq-menu-open');});
  if(backdrop)backdrop.addEventListener('click',closeMenu);
  root.querySelectorAll('.mq-nav').forEach(function(link){link.addEventListener('click',closeMenu);});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')closeMenu();});

  function esc(v){
    return String(v==null?'':v).replace(/[&<>"']/g,function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }

  root.querySelectorAll('[data-mq-filter]').forEach(function(input){
    input.addEventListener('input',function(){
      var q=input.value.trim().toLowerCase();
      root.querySelectorAll('[data-mq-row]').forEach(function(row){
        row.style.display=!q||row.textContent.toLowerCase().indexOf(q)!==-1?'':'none';
      });
    });
  });

  root.querySelectorAll('[data-mq-refresh]').forEach(function(btn){
    btn.addEventListener('click',function(){
      btn.disabled=true;
      window.location.reload();
    });
  });

  root.querySelectorAll('[data-mq-copy]').forEach(function(btn){
    btn.addEventListener('click',function(){
      var value=btn.getAttribute('data-mq-copy')||'';
      if(!navigator.clipboard)return;
      navigator.clipboard.writeText(value).then(function(){
        var old=btn.textContent;
        btn.textContent='تم النسخ';
        setTimeout(function(){btn.textContent=old;},1200);
      });
    });
  });

  root.querySelectorAll('[data-mq-accordion]').forEach(function(btn){
    btn.addEventListener('click',function(){
      var target=document.querySelector(btn.getAttribute('data-mq-accordion'));
      if(target)target.classList.toggle('is-open');
    });
  });

  // Never invent operational records. The frontend only enhances server-rendered MUTQAN data.
  // External AI/WhatsApp providers remain explicitly marked as setup-required until configured.
  var status=root.querySelector('[data-mq-live-clock]');
  if(status){
    function tick(){
      var now=new Date();
      status.textContent=now.toLocaleTimeString('ar-SA',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
    }
    tick();
    setInterval(tick,1000);
  }
});
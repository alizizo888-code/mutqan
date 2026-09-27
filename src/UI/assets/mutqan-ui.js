document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('.mq-shell').forEach(function(shell){
    var pages=shell.querySelectorAll('.mq-page');
    var links=shell.querySelectorAll('[data-mq-route]');
    function show(route,writeHash){
      var target=shell.querySelector('[data-mq-page="'+route+'"]')||shell.querySelector('[data-mq-page="home"]');
      pages.forEach(function(p){p.classList.toggle('mq-visible',p===target)});
      links.forEach(function(a){a.classList.toggle('mq-active',a.getAttribute('data-mq-route')===route)});
      if(writeHash && history.replaceState) history.replaceState(null,'','#'+route);
      shell.classList.remove('mq-sidebar-open');
    }
    links.forEach(function(a){a.addEventListener('click',function(e){
      var route=a.getAttribute('data-mq-route');
      if(!shell.querySelector('[data-mq-page="'+route+'"]')) return;
      e.preventDefault(); show(route,true);
    })});
    var toggle=shell.querySelector('[data-mq-toggle]');
    if(toggle) toggle.addEventListener('click',function(){shell.classList.toggle('mq-sidebar-open')});
    var theme=shell.querySelector('[data-mq-theme]');
    if(theme) theme.addEventListener('click',function(){
      shell.classList.toggle('mq-dark');
      try{localStorage.setItem('mq-dark',shell.classList.contains('mq-dark')?'1':'0')}catch(e){}
    });
    try{if(localStorage.getItem('mq-dark')==='1')shell.classList.add('mq-dark')}catch(e){}
    shell.querySelectorAll('[data-mq-expand]').forEach(function(btn){btn.addEventListener('click',function(){
      var group=btn.closest('.mq-service-group'); if(group) group.classList.toggle('mq-open');
    })});
    shell.querySelectorAll('[data-mq-card]').forEach(function(card){
      card.addEventListener('click',function(){
        var routes=['services','orders','radar','offers'];
        var route=routes[Number(card.getAttribute('data-mq-card'))%routes.length];
        var target=shell.querySelector('[data-mq-page="'+route+'"]');
        if(target) show(route,true);
      });
    });
    var route=(location.hash||'').replace('#','');
    show(route||'home',false);
  });
});
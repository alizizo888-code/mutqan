(function(){
 document.addEventListener('DOMContentLoaded',function(){
   if(!window.MUTQAN_THEME || !MUTQAN_THEME.splashEnabled) return;
   var app=document.querySelector('.mq-app'); if(!app) return;
   var splash=document.createElement('div'); splash.className='mq-splash';
   var img=app.querySelector('.mq-brand img');
   var logo=img ? img.getAttribute('src') : '';
   splash.innerHTML='<div class="mq-splash-card">'+(logo?'<img class="mq-splash-logo" src="'+logo.replace(/"/g,'&quot;')+'" alt="">':'')+'<div class="mq-splash-name">مُتقِن</div><div class="mq-splash-tagline">حلول الصيانة والمتابعة بسهولة وأمان</div></div>';
   document.body.appendChild(splash);
   window.setTimeout(function(){splash.classList.add('is-hidden');window.setTimeout(function(){splash.remove();},400);},Math.max(800,Number(MUTQAN_THEME.duration)||1800));
 });
})();
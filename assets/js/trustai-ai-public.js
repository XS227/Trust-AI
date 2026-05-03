/* TrustAI Public Chatbot — for landing pages / public pages
 * Floating chat widget. No login required. Talks about TrustAI itself.
 */
(function () {
  'use strict';
  if (window.__trustaiPubChatLoaded) return;
  window.__trustaiPubChatLoaded = true;

  var STYLE =
'.tpc-fab{position:fixed;bottom:24px;right:24px;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:0;cursor:pointer;box-shadow:0 8px 24px rgba(37,99,235,.4);display:flex;align-items:center;justify-content:center;z-index:9998;transition:transform .15s}'+
'.tpc-fab:hover{transform:scale(1.05)}'+
'.tpc-fab svg{width:26px;height:26px}'+
'.tpc-fab-pulse{position:absolute;top:-2px;right:-2px;width:14px;height:14px;background:#10b981;border:2px solid #fff;border-radius:50%}'+
'.tpc-bubble{position:fixed;bottom:96px;right:24px;background:#fff;color:#0f172a;padding:10px 14px;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.15);font-size:13px;font-weight:500;max-width:240px;z-index:9997;border:1px solid #e2e8f0;animation:tpcBubblePop .35s ease}'+
'.tpc-bubble::after{content:"";position:absolute;bottom:-8px;right:18px;border:8px solid transparent;border-top-color:#fff;border-bottom:0}'+
'@keyframes tpcBubblePop{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}'+
'.tpc-chat{position:fixed;bottom:96px;right:24px;width:380px;max-width:calc(100vw - 32px);height:540px;max-height:calc(100vh - 120px);background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.18);z-index:9999;display:none;flex-direction:column;overflow:hidden}'+
'.tpc-chat.open{display:flex}'+
'.tpc-chat-head{padding:14px 16px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;display:flex;justify-content:space-between;align-items:center}'+
'.tpc-chat-head h4{margin:0;font-size:15px;font-weight:600}'+
'.tpc-chat-head .sub{font-size:12px;opacity:.85;margin-top:2px}'+
'.tpc-chat-close{background:transparent;border:0;color:#fff;cursor:pointer;font-size:22px;line-height:1;padding:4px 8px}'+
'.tpc-chat-body{flex:1;overflow-y:auto;padding:14px;background:#f8fafc}'+
'.tpc-msg{margin-bottom:12px;max-width:85%;padding:10px 12px;border-radius:12px;font-size:13px;line-height:1.5;white-space:pre-wrap}'+
'.tpc-msg.user{margin-left:auto;background:#2563eb;color:#fff;border-bottom-right-radius:4px}'+
'.tpc-msg.bot{background:#fff;color:#0f172a;border:1px solid #e2e8f0;border-bottom-left-radius:4px}'+
'.tpc-chat-foot{padding:12px;border-top:1px solid #e2e8f0;background:#fff;display:flex;gap:8px}'+
'.tpc-chat-foot input{flex:1;padding:10px 12px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;font-size:13px}'+
'.tpc-chat-foot input:focus{outline:none;border-color:#2563eb}'+
'.tpc-chat-foot button{padding:10px 16px;border-radius:10px;border:0;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;font-size:13px}'+
'.tpc-chat-foot button:disabled{opacity:.5;cursor:not-allowed}'+
'.tpc-suggest{padding:8px 14px;display:flex;flex-wrap:wrap;gap:6px;border-top:1px solid #e2e8f0;background:#fff}'+
'.tpc-suggest button{font-size:11px;padding:6px 10px;border-radius:8px;border:1px solid #cbd5e1;background:#f8fafc;color:#475569;cursor:pointer}'+
'.tpc-suggest button:hover{background:#eef4ff;border-color:#2563eb;color:#2563eb}'+
'@media(max-width:560px){.tpc-chat{right:8px;left:8px;width:auto;bottom:90px}.tpc-fab{bottom:16px;right:16px}.tpc-bubble{display:none}}';

  function injectStyle(){var s=document.createElement('style');s.textContent=STYLE;document.head.appendChild(s);}
  function esc(t){return String(t||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}

  function getLang(){return localStorage.getItem('trustai_lang')||'en';}

  var TXT = {
    en: {greet:"Hi! I'm TrustAI Coach. Ask me anything about TrustAI — how it works, how to sign up, how to integrate with Shopify.",placeholder:"Ask me anything...",send:"Send",title:"TrustAI Coach",sub:"Online — ask me anything",bubble:"👋 Hi! Need help?",suggestions:["What is TrustAI?","How do I sign up as ambassador?","Setup with Shopify?","Who built TrustAI?"]},
    no: {greet:"Hei! Jeg er TrustAI Coach. Spør meg om alt om TrustAI — hvordan det fungerer, hvordan registrere deg, integrasjon med Shopify.",placeholder:"Spør meg om alt...",send:"Send",title:"TrustAI Coach",sub:"Online — spør meg om alt",bubble:"👋 Hei! Trenger du hjelp?",suggestions:["Hva er TrustAI?","Hvordan registrere meg som ambassadør?","Oppsett med Shopify?","Hvem bygde TrustAI?"]}
  };

  var history=[];
  var fab,chat,bubble,bubbleTimer;

  function build(){
    fab=document.createElement('button');
    fab.className='tpc-fab';
    fab.title='Chat with TrustAI Coach';
    fab.innerHTML='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span class="tpc-fab-pulse"></span>';
    document.body.appendChild(fab);

    chat=document.createElement('div');
    chat.className='tpc-chat';
    document.body.appendChild(chat);

    fab.onclick=function(){
      removeBubble();
      chat.classList.toggle('open');
      if (chat.classList.contains('open')) {
        renderChat();
        var inp=chat.querySelector('input');
        if(inp)inp.focus();
      }
    };

    // Pop greeting bubble after 4s
    bubbleTimer=setTimeout(showBubble,4000);
  }

  function showBubble(){
    if (chat.classList.contains('open')) return;
    var t=TXT[getLang()]||TXT.en;
    bubble=document.createElement('div');
    bubble.className='tpc-bubble';
    bubble.textContent=t.bubble;
    bubble.onclick=function(){fab.click();};
    document.body.appendChild(bubble);
    setTimeout(removeBubble,8000);
  }

  function removeBubble(){
    if (bubbleTimer) clearTimeout(bubbleTimer);
    if (bubble){bubble.remove();bubble=null;}
  }

  function renderChat(){
    var t=TXT[getLang()]||TXT.en;
    chat.innerHTML='<div class="tpc-chat-head"><div><h4>🤖 '+esc(t.title)+'</h4><div class="sub">'+esc(t.sub)+'</div></div><button class="tpc-chat-close">×</button></div>'+
      '<div class="tpc-chat-body" id="tpcBody"><div class="tpc-msg bot">'+esc(t.greet)+'</div></div>'+
      '<div class="tpc-suggest" id="tpcSuggest"></div>'+
      '<div class="tpc-chat-foot"><input placeholder="'+esc(t.placeholder)+'"><button>'+esc(t.send)+'</button></div>';
    var input=chat.querySelector('input'),sendBtn=chat.querySelector('.tpc-chat-foot button'),body=chat.querySelector('#tpcBody'),suggest=chat.querySelector('#tpcSuggest');
    chat.querySelector('.tpc-chat-close').onclick=function(){chat.classList.remove('open');};
    t.suggestions.forEach(function(s){
      var b=document.createElement('button');b.textContent=s;b.onclick=function(){input.value=s;send();};suggest.appendChild(b);
    });

    // Restore previous chat history
    history.forEach(function(h){
      if(h.role==='user'){var u=document.createElement('div');u.className='tpc-msg user';u.textContent=h.content;body.appendChild(u);}
      else if(h.role==='assistant'){var a=document.createElement('div');a.className='tpc-msg bot';a.textContent=h.content;body.appendChild(a);}
    });
    body.scrollTop=body.scrollHeight;

    function send(){
      var text=input.value.trim();
      if(!text)return;
      input.value='';sendBtn.disabled=true;
      var u=document.createElement('div');u.className='tpc-msg user';u.textContent=text;body.appendChild(u);
      var l=document.createElement('div');l.className='tpc-msg bot';l.textContent='🤔 ...';l.style.opacity='.6';body.appendChild(l);
      body.scrollTop=body.scrollHeight;
      fetch('/api/ai/public-chat.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({message:text,history:history.slice(-8),lang:getLang()})
      }).then(function(r){return r.json();}).then(function(d){
        l.remove();
        var b=document.createElement('div');b.className='tpc-msg bot';
        b.textContent=d.ok?d.reply:(d.reply||'Sorry, could not answer right now.');
        body.appendChild(b);body.scrollTop=body.scrollHeight;
        if(d.ok){history.push({role:'user',content:text});history.push({role:'assistant',content:d.reply});}
      }).catch(function(){
        l.remove();
        var b=document.createElement('div');b.className='tpc-msg bot';b.textContent='Network error. Try again.';body.appendChild(b);
      }).finally(function(){sendBtn.disabled=false;input.focus();});
    }
    sendBtn.onclick=send;
    input.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();}});
  }

  // Re-render when language changes
  document.addEventListener('trustai:langchange',function(){
    if (chat && chat.classList.contains('open')) renderChat();
  });

  function init(){injectStyle();build();}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();

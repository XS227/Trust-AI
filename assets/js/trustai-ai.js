/* TrustAI AI Coach widget
 * - Insights-kort på toppen av dashboards (sett <div data-tai-insights></div>)
 * - Floating chat-knapp nederst til høyre (auto-injisert)
 * Krever: bruker er innlogget, /api/ai/insights.php og /api/ai/chat.php
 */
(function () {
  'use strict';
  if (window.__trustaiAiLoaded) return;
  window.__trustaiAiLoaded = true;

  const STYLE = `
.tai-insights{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;margin:0 0 18px}
.tai-insight{background:linear-gradient(135deg,#0b1220,#111827);border:1px solid #334155;border-radius:14px;padding:14px 16px;color:#e2e8f0;position:relative;overflow:hidden}
.tai-insight.high{border-color:#f97316}
.tai-insight.high::before{content:"";position:absolute;top:0;left:0;width:4px;height:100%;background:#f97316}
.tai-insight.medium{border-color:#3b82f6}
.tai-insight.medium::before{content:"";position:absolute;top:0;left:0;width:4px;height:100%;background:#3b82f6}
.tai-insight.low{border-color:#475569}
.tai-insight-head{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.tai-insight-icon{font-size:20px}
.tai-insight-title{font-weight:600;font-size:14px}
.tai-insight-msg{font-size:13px;color:#cbd5e1;line-height:1.5;margin:6px 0}
.tai-insight-action{margin-top:10px;font-size:12px;color:#94a3b8;border-top:1px solid #1e293b;padding-top:8px}
.tai-insight-action b{color:#60a5fa}
.tai-insights-loading{padding:14px;background:#0b1220;border:1px solid #334155;border-radius:14px;color:#94a3b8;text-align:center;font-size:13px}
.tai-insights-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.tai-insights-title{color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:.8px;font-weight:600;display:flex;align-items:center;gap:6px}
.tai-refresh-btn{background:transparent;border:0;color:#60a5fa;font-size:12px;cursor:pointer;padding:4px 8px;border-radius:6px}
.tai-refresh-btn:hover{background:#1e293b}

.tai-fab{position:fixed;bottom:24px;right:24px;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:0;cursor:pointer;box-shadow:0 8px 24px rgba(37,99,235,.4);display:flex;align-items:center;justify-content:center;z-index:9998;transition:transform .15s}
.tai-fab:hover{transform:scale(1.05)}
.tai-fab svg{width:26px;height:26px}
.tai-fab-pulse{position:absolute;top:-2px;right:-2px;width:14px;height:14px;background:#10b981;border:2px solid #0f172a;border-radius:50%}

.tai-chat{position:fixed;bottom:96px;right:24px;width:380px;max-width:calc(100vw - 32px);height:540px;max-height:calc(100vh - 120px);background:#0b1220;border:1px solid #334155;border-radius:16px;box-shadow:0 24px 60px rgba(0,0,0,.5);z-index:9999;display:none;flex-direction:column;overflow:hidden}
.tai-chat.open{display:flex}
.tai-chat-head{padding:14px 16px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;display:flex;justify-content:space-between;align-items:center}
.tai-chat-head h4{margin:0;font-size:15px;font-weight:600}
.tai-chat-head .sub{font-size:12px;opacity:.85;margin-top:2px}
.tai-chat-close{background:transparent;border:0;color:#fff;cursor:pointer;font-size:22px;line-height:1;padding:4px 8px}
.tai-chat-body{flex:1;overflow-y:auto;padding:14px;background:#0f172a}
.tai-msg{margin-bottom:12px;max-width:85%;padding:10px 12px;border-radius:12px;font-size:13px;line-height:1.5;white-space:pre-wrap}
.tai-msg.user{margin-left:auto;background:#2563eb;color:#fff;border-bottom-right-radius:4px}
.tai-msg.bot{background:#1e293b;color:#e2e8f0;border-bottom-left-radius:4px}
.tai-msg.bot.loading{opacity:.7;font-style:italic}
.tai-chat-foot{padding:12px;border-top:1px solid #1e293b;background:#0b1220;display:flex;gap:8px}
.tai-chat-foot input{flex:1;padding:10px 12px;border-radius:10px;border:1px solid #334155;background:#0f172a;color:#e2e8f0;font-size:13px}
.tai-chat-foot input:focus{outline:none;border-color:#2563eb}
.tai-chat-foot button{padding:10px 16px;border-radius:10px;border:0;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;font-size:13px}
.tai-chat-foot button:disabled{opacity:.5;cursor:not-allowed}
.tai-chat-suggest{padding:8px 14px;display:flex;flex-wrap:wrap;gap:6px;border-top:1px solid #1e293b;background:#0b1220}
.tai-chat-suggest button{font-size:11px;padding:6px 10px;border-radius:8px;border:1px solid #334155;background:#0f172a;color:#cbd5e1;cursor:pointer}
.tai-chat-suggest button:hover{background:#1e293b;border-color:#475569}
@media(max-width:560px){.tai-chat{right:8px;left:8px;width:auto;bottom:90px}.tai-fab{bottom:16px;right:16px}}
`;

  function injectStyle() {
    const s = document.createElement('style');
    s.textContent = STYLE;
    document.head.appendChild(s);
  }

  function escapeHtml(t) {
    return String(t || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
  }

  /* INSIGHTS CARDS */
  async function loadInsights(container, refresh) {
    container.innerHTML = '<div class="tai-insights-loading">🤖 AI tenker...</div>';
    try {
      const res = await fetch('/api/ai/insights.php' + (refresh ? '?refresh=1' : ''), { credentials: 'include' });
      const data = await res.json();
      if (!data.ok || !Array.isArray(data.insights) || data.insights.length === 0) {
        container.innerHTML = '<div class="tai-insights-loading">Ingen insikter tilgjengelig akkurat nå.</div>';
        return;
      }
      container.innerHTML = data.insights.map(function(i){
        return '<div class="tai-insight ' + escapeHtml(i.priority || 'medium') + '">' +
          '<div class="tai-insight-head">' +
            '<span class="tai-insight-icon">' + escapeHtml(i.icon || '💡') + '</span>' +
            '<span class="tai-insight-title">' + escapeHtml(i.title || 'Innsikt') + '</span>' +
          '</div>' +
          '<div class="tai-insight-msg">' + escapeHtml(i.message || '') + '</div>' +
          (i.action ? '<div class="tai-insight-action"><b>Neste steg:</b> ' + escapeHtml(i.action) + '</div>' : '') +
        '</div>';
      }).join('');
    } catch (e) {
      container.innerHTML = '<div class="tai-insights-loading">Kunne ikke laste insikter.</div>';
    }
  }

  function mountInsights() {
    const target = document.querySelector('[data-tai-insights]');
    if (!target) return;
    target.innerHTML = '<div class="tai-insights-header">' +
      '<div class="tai-insights-title">🤖 AI Coach — Innsikt for deg</div>' +
      '<button class="tai-refresh-btn" id="taiRefreshBtn">↻ Oppdater</button>' +
      '</div>' +
      '<div class="tai-insights" id="taiInsightsBox"></div>';
    const box = target.querySelector('#taiInsightsBox');
    loadInsights(box, false);
    target.querySelector('#taiRefreshBtn').onclick = function(){ loadInsights(box, true); };
  }

  /* CHAT WIDGET */
  const history = [];

  function buildChat() {
    const fab = document.createElement('button');
    fab.className = 'tai-fab';
    fab.title = 'Spør AI Coach';
    fab.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span class="tai-fab-pulse"></span>';
    document.body.appendChild(fab);

    const chat = document.createElement('div');
    chat.className = 'tai-chat';
    chat.innerHTML = '<div class="tai-chat-head">' +
        '<div><h4>🤖 TrustAI Coach</h4><div class="sub">Din personlige rådgiver</div></div>' +
        '<button class="tai-chat-close">×</button>' +
      '</div>' +
      '<div class="tai-chat-body" id="taiChatBody">' +
        '<div class="tai-msg bot">Hei! Jeg er din AI Coach. Spør meg om alt — fra delingstips til hvordan optimere ambassadør-programmet ditt. Hva lurer du på?</div>' +
      '</div>' +
      '<div class="tai-chat-suggest" id="taiSuggest"></div>' +
      '<div class="tai-chat-foot">' +
        '<input id="taiInput" placeholder="Skriv ditt spørsmål...">' +
        '<button id="taiSend">Send</button>' +
      '</div>';
    document.body.appendChild(chat);

    const input = chat.querySelector('#taiInput');
    const sendBtn = chat.querySelector('#taiSend');
    const body = chat.querySelector('#taiChatBody');
    const suggest = chat.querySelector('#taiSuggest');

    fab.onclick = function(){
      chat.classList.toggle('open');
      if (chat.classList.contains('open')) input.focus();
    };
    chat.querySelector('.tai-chat-close').onclick = function(){ chat.classList.remove('open'); };

    const suggestions = ['Hvordan tjener jeg mer?','Beste delingstekst?','Hvilken kanal konverterer best?','Anbefal neste handling'];
    suggest.innerHTML = suggestions.map(function(s){ return '<button>' + escapeHtml(s) + '</button>'; }).join('');
    suggest.querySelectorAll('button').forEach(function(b){
      b.onclick = function(){ input.value = b.textContent; send(); };
    });

    async function send() {
      const text = input.value.trim();
      if (!text) return;
      input.value = '';
      sendBtn.disabled = true;

      const userMsg = document.createElement('div');
      userMsg.className = 'tai-msg user';
      userMsg.textContent = text;
      body.appendChild(userMsg);

      const loadingMsg = document.createElement('div');
      loadingMsg.className = 'tai-msg bot loading';
      loadingMsg.textContent = '🤔 tenker...';
      body.appendChild(loadingMsg);
      body.scrollTop = body.scrollHeight;

      try {
        const res = await fetch('/api/ai/chat.php', {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ message: text, history: history.slice(-8) }),
        });
        const data = await res.json();
        loadingMsg.remove();

        const botMsg = document.createElement('div');
        botMsg.className = 'tai-msg bot';
        botMsg.textContent = data.ok ? data.reply : ('Beklager, fikk ikke svar: ' + (data.error || 'ukjent'));
        body.appendChild(botMsg);
        body.scrollTop = body.scrollHeight;

        if (data.ok) {
          history.push({ role: 'user', content: text });
          history.push({ role: 'assistant', content: data.reply });
        }
      } catch (e) {
        loadingMsg.remove();
        const errMsg = document.createElement('div');
        errMsg.className = 'tai-msg bot';
        errMsg.textContent = 'Nettverksfeil. Prøv igjen.';
        body.appendChild(errMsg);
      } finally {
        sendBtn.disabled = false;
        input.focus();
      }
    }

    sendBtn.onclick = send;
    input.addEventListener('keydown', function(e){
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });
  }

  function init() {
    injectStyle();
    mountInsights();
    buildChat();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

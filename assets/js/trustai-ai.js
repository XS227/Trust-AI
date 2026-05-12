/* TrustAI AI Coach widget
 * - Insights cards on top of dashboards (place <div data-tai-insights></div>)
 * - Floating chat button bottom-right (auto-injected)
 * Requires: logged-in user, /api/ai/insights.php and /api/ai/chat.php
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

.tai-fab{position:fixed;bottom:20px;right:20px;width:54px;height:54px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:0;cursor:pointer;box-shadow:0 8px 24px rgba(37,99,235,.4);display:flex;align-items:center;justify-content:center;z-index:9998;transition:transform .15s}
.tai-fab:hover{transform:scale(1.05)}
.tai-fab svg{width:26px;height:26px}
.tai-fab-pulse{position:absolute;top:-2px;right:-2px;width:14px;height:14px;background:#10b981;border:2px solid #0f172a;border-radius:50%}

.tai-chat{position:fixed;bottom:86px;right:20px;width:min(340px,calc(100vw - 24px));height:min(500px,calc(100vh - 120px));background:#ffffff;border:1px solid #dbe4f0;border-radius:14px;box-shadow:0 16px 42px rgba(15,23,42,.18);z-index:1200;display:none;flex-direction:column;overflow:hidden}
.tai-chat.open{display:flex}
.tai-chat-head{padding:14px 16px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;display:flex;justify-content:space-between;align-items:center}
.tai-chat-head h4{margin:0;font-size:15px;font-weight:600}
.tai-chat-head .sub{font-size:12px;opacity:.85;margin-top:2px;display:flex;align-items:center;gap:6px}
.tai-chat-close{background:transparent;border:0;color:#fff;cursor:pointer;font-size:22px;line-height:1;padding:4px 8px}
.tai-chat-body{flex:1;overflow-y:auto;padding:14px;background:#f8fafc}
.tai-msg{margin-bottom:12px;max-width:85%;padding:10px 12px;border-radius:12px;font-size:13px;line-height:1.5;white-space:pre-wrap}
.tai-msg.user{margin-left:auto;background:#2563eb;color:#fff;border-bottom-right-radius:4px}
.tai-msg.bot{background:#e2e8f0;color:#0f172a;border-bottom-left-radius:4px}
.tai-chat-foot{padding:12px;border-top:1px solid #e2e8f0;background:#fff;display:flex;gap:8px}
.tai-chat-foot input{flex:1;padding:10px 12px;border-radius:10px;border:1px solid #334155;background:#0f172a;color:#e2e8f0;font-size:13px}
.tai-chat-foot input:focus{outline:none;border-color:#2563eb}
.tai-chat-foot button{padding:10px 16px;border-radius:10px;border:0;background:#2563eb;color:#fff;font-weight:600;cursor:pointer;font-size:13px}
.tai-chat-foot button:disabled{opacity:.5;cursor:not-allowed}
.tai-chat-suggest{padding:8px 14px;display:flex;flex-wrap:wrap;gap:6px;border-top:1px solid #e2e8f0;background:#fff}
.tai-chat-suggest button{font-size:11px;padding:6px 10px;border-radius:8px;border:1px solid #334155;background:#0f172a;color:#cbd5e1;cursor:pointer}
.tai-chat-suggest button:hover{background:#1e293b;border-color:#475569}

.tai-demo-badge{background:rgba(255,255,255,.18);color:#fff;font-size:9px;font-weight:800;padding:2px 7px;border-radius:4px;letter-spacing:.06em;text-transform:uppercase;vertical-align:middle}
.tai-demo-badge-insight{display:inline-block;background:#1e3a5f;color:#93c5fd;font-size:9px;font-weight:700;padding:2px 6px;border-radius:4px;letter-spacing:.05em;text-transform:uppercase;margin-left:6px;vertical-align:middle}

.tai-typing{display:inline-flex;align-items:center;gap:4px;padding:2px 0}
.tai-dot{width:7px;height:7px;border-radius:50%;background:#64748b;display:inline-block;animation:taiDot 1.2s infinite ease-in-out}
.tai-dot:nth-child(2){animation-delay:.2s}
.tai-dot:nth-child(3){animation-delay:.4s}
@keyframes taiDot{0%,80%,100%{transform:scale(.7);opacity:.45}40%{transform:scale(1);opacity:1}}

@media(max-width:860px){.tai-chat{position:static;width:100%;height:420px;margin-top:14px;display:flex}.tai-fab{display:none}}
`;

  function injectStyle() {
    const s = document.createElement('style');
    s.textContent = STYLE;
    document.head.appendChild(s);
  }

  function escapeHtml(t) {
    return String(t || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
  }

  let _demoMode = false;

  function markDemoMode() {
    if (_demoMode) return;
    _demoMode = true;
    // Badge in chat header
    const sub = document.getElementById('taiChatSub');
    if (sub && !sub.querySelector('.tai-demo-badge')) {
      sub.insertAdjacentHTML('beforeend', '<span class="tai-demo-badge">Demo AI</span>');
    }
    // Badge in insights header
    const title = document.querySelector('.tai-insights-title');
    if (title && !title.querySelector('.tai-demo-badge-insight')) {
      title.insertAdjacentHTML('beforeend', '<span class="tai-demo-badge-insight">Demo</span>');
    }
  }

  /* INSIGHTS CARDS */
  async function loadInsights(container, refresh) {
    container.innerHTML = '<div class="tai-insights-loading"><span class="tai-typing"><span class="tai-dot"></span><span class="tai-dot"></span><span class="tai-dot"></span></span> <span style="margin-left:6px">AI henter innsikter…</span></div>';
    try {
      const res = await fetch('/api/ai/insights.php' + (refresh ? '?refresh=1' : ''), { credentials: 'include' });
      const data = await res.json();
      if (data.demo) markDemoMode();
      if (!data.ok || !Array.isArray(data.insights) || data.insights.length === 0) {
        container.innerHTML = '<div class="tai-insights-loading">Ingen innsikter tilgjengelig akkurat nå.</div>';
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
      container.innerHTML = '<div class="tai-insights-loading">Kunne ikke laste innsikter. Sjekk tilkoblingen og prøv igjen.</div>';
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
        '<div><h4>🤖 TrustAI Coach</h4><div class="sub" id="taiChatSub">Din personlige salgsrådgiver</div></div>' +
        '<button class="tai-chat-close" aria-label="Lukk">×</button>' +
      '</div>' +
      '<div class="tai-chat-body" id="taiChatBody">' +
        '<div class="tai-msg bot">Hei! Jeg er TrustAI Coach. Spør meg om kanaler, tekster, timing, konverteringer eller hva som helst om ambassadørprogrammet ditt. 👋</div>' +
      '</div>' +
      '<div class="tai-chat-suggest" id="taiSuggest"></div>' +
      '<div class="tai-chat-foot">' +
        '<input id="taiInput" placeholder="Skriv ditt spørsmål…" autocomplete="off">' +
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

    const suggestions = [
      'Hvordan tjener jeg mer?',
      'Beste delingstekst?',
      'Hvilken kanal konverterer best?',
      'Beste tidspunkt å dele?',
    ];
    suggest.innerHTML = suggestions.map(function(s){ return '<button>' + escapeHtml(s) + '</button>'; }).join('');
    suggest.querySelectorAll('button').forEach(function(b){
      b.onclick = function(){ input.value = b.textContent; send(); };
    });

    function makeTypingDots() {
      const el = document.createElement('div');
      el.className = 'tai-msg bot';
      el.innerHTML = '<span class="tai-typing"><span class="tai-dot"></span><span class="tai-dot"></span><span class="tai-dot"></span></span>';
      return el;
    }

    async function send() {
      const text = input.value.trim();
      if (!text) return;
      input.value = '';
      sendBtn.disabled = true;

      const userMsg = document.createElement('div');
      userMsg.className = 'tai-msg user';
      userMsg.textContent = text;
      body.appendChild(userMsg);

      const loadingEl = makeTypingDots();
      body.appendChild(loadingEl);
      body.scrollTop = body.scrollHeight;

      try {
        const res = await fetch('/api/ai/chat.php', {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ message: text, history: history.slice(-8) }),
        });

        if (!res.ok) {
          throw new Error('http_' + res.status);
        }

        const data = await res.json();
        loadingEl.remove();

        if (data.demo) markDemoMode();

        const botMsg = document.createElement('div');
        botMsg.className = 'tai-msg bot';
        botMsg.textContent = data.ok && data.reply
          ? data.reply
          : 'Beklager, fikk ikke svar akkurat nå. Prøv igjen om litt.';
        body.appendChild(botMsg);
        body.scrollTop = body.scrollHeight;

        if (data.ok && data.reply) {
          history.push({ role: 'user', content: text });
          history.push({ role: 'assistant', content: data.reply });
        }
      } catch (e) {
        loadingEl.remove();
        const errMsg = document.createElement('div');
        errMsg.className = 'tai-msg bot';
        errMsg.textContent = 'Nettverksfeil. Sjekk tilkoblingen og prøv igjen.';
        body.appendChild(errMsg);
        body.scrollTop = body.scrollHeight;
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

  async function checkAiStatus() {
    try {
      const data = await fetch('/api/ai/status.php', { credentials: 'include' }).then(r => r.json());
      if (data.ai_enabled === false) markDemoMode();
    } catch (_) {
      // Non-critical — demo badge will appear on first chat response if needed
    }
  }

  function init() {
    injectStyle();
    mountInsights();
    buildChat();
    checkAiStatus();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

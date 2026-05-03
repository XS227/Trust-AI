(function () {
  // Skip on pages that already have a custom header (forsiden)
  if (document.querySelector('header.site-header') || document.querySelector('.ta-global-header')) return;
  if (document.body.classList.contains('no-global-header')) return;

  const style = document.createElement('style');
  style.textContent = `
    .ta-global-header{position:sticky;top:0;z-index:100;background:rgba(11,18,32,.95);backdrop-filter:blur(8px);border-bottom:1px solid rgba(148,163,184,.12)}
    .ta-header-wrap{max-width:1200px;margin:0 auto;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px}
    .ta-header-brand{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:20px;letter-spacing:-.02em;text-decoration:none}
    .ta-header-brand img{height:32px;width:auto}
    .ta-header-brand-text{display:flex;align-items:center;gap:0}
    .ta-header-brand-text span{color:#60a5fa}
    .ta-header-nav{display:flex;align-items:center;gap:6px}
    .ta-header-nav a{color:#cbd5e1;text-decoration:none;font-size:14px;padding:8px 12px;border-radius:8px;font-weight:500}
    .ta-header-nav a:hover{color:#fff;background:rgba(148,163,184,.08)}
    .ta-header-cta{padding:8px 14px;border-radius:8px;background:#2563eb;color:#fff !important;font-weight:600;font-size:13px}
    .ta-header-cta:hover{background:#1d4ed8 !important}
    .ta-header-lang{display:flex;gap:4px;margin-left:6px;border-left:1px solid rgba(148,163,184,.2);padding-left:10px}
    .ta-header-lang button{background:transparent;border:1px solid transparent;color:#cbd5e1;padding:6px 10px;border-radius:6px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:4px}
    .ta-header-lang button:hover{background:rgba(148,163,184,.12);color:#fff}
    .ta-header-lang button.active{background:#2563eb;color:#fff;border-color:#2563eb}
    .ta-header-burger{display:none;background:transparent;border:0;color:#fff;cursor:pointer;font-size:22px;padding:8px}
    @media (max-width:880px){
      .ta-header-nav{display:none;position:absolute;top:100%;left:0;right:0;flex-direction:column;background:#0b1220;border-bottom:1px solid rgba(148,163,184,.12);padding:14px 20px;gap:8px;align-items:stretch}
      .ta-header-nav.open{display:flex}
      .ta-header-nav a{padding:10px 14px}
      .ta-header-burger{display:block}
      .ta-header-lang{margin-left:0;border-left:0;border-top:1px solid rgba(148,163,184,.2);padding:10px 0 0;justify-content:flex-start}
    }
  `;
  document.head.appendChild(style);

  const T = {
    en: { home:'Home', howItWorks:'How it works', useCases:'Use cases', login:'Log in', register:'Become ambassador' },
    no: { home:'Hjem', howItWorks:'Slik virker det', useCases:'Bruksområder', login:'Logg inn', register:'Bli ambassadør' },
  };

  function getLang(){ return localStorage.getItem('trustai_lang') || 'en'; }
  function setLang(lang){
    localStorage.setItem('trustai_lang', lang);
    document.documentElement.lang = lang;
    render();
    document.dispatchEvent(new CustomEvent('trustai:langchange', { detail: { lang } }));
  }

  function render() {
    const lang = getLang();
    const t = T[lang] || T.en;
    let header = document.querySelector('.ta-global-header');
    if (!header) {
      header = document.createElement('header');
      header.className = 'ta-global-header';
      document.body.insertBefore(header, document.body.firstChild);
    }
    header.innerHTML = `
      <div class="ta-header-wrap">
        <a href="/" class="ta-header-brand">
          <img src="/logo.png" alt="TrustAi">
          <span class="ta-header-brand-text">Trust<span>Ai</span></span>
        </a>
        <button class="ta-header-burger" aria-label="Menu">☰</button>
        <nav class="ta-header-nav">
          <a href="/">${t.home}</a>
          <a href="/how-it-works.html">${t.howItWorks}</a>
          <a href="/#use-cases">${t.useCases}</a>
          <a href="/login.html">${t.login}</a>
          <a href="/ambassador-signup.html" class="ta-header-cta">${t.register}</a>
          <div class="ta-header-lang">
            <button data-lang="en" class="${lang==='en'?'active':''}" type="button">🇬🇧 EN</button>
            <button data-lang="no" class="${lang==='no'?'active':''}" type="button">🇳🇴 NO</button>
          </div>
        </nav>
      </div>`;
    const burger = header.querySelector('.ta-header-burger');
    const nav = header.querySelector('.ta-header-nav');
    burger.onclick = () => nav.classList.toggle('open');
    header.querySelectorAll('.ta-header-lang button').forEach(b => {
      b.onclick = (e) => { e.stopPropagation(); setLang(b.dataset.lang); };
    });
  }

  // Page-wide language application: any element with data-en/data-no attributes
  function applyI18n() {
    const lang = getLang();
    document.querySelectorAll('[data-en][data-no]').forEach(el => {
      const txt = el.getAttribute('data-' + lang);
      if (txt) el.textContent = txt;
    });
    document.querySelectorAll('[data-en-html][data-no-html]').forEach(el => {
      const html = el.getAttribute('data-' + lang + '-html');
      if (html) el.innerHTML = html;
    });
    // Page title
    const titleEn = document.documentElement.getAttribute('data-title-en');
    const titleNo = document.documentElement.getAttribute('data-title-no');
    if (titleEn && titleNo) document.title = lang === 'no' ? titleNo : titleEn;
  }

  document.addEventListener('trustai:langchange', applyI18n);

  if (!localStorage.getItem('trustai_lang')) {
    const browserLang = (navigator.language || '').toLowerCase();
    localStorage.setItem('trustai_lang', browserLang.startsWith('no') ? 'no' : 'en');
  }

  function init(){ render(); applyI18n(); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();

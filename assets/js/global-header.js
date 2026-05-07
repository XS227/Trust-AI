(function () {
  const appPaths=['/ambassador-dashboard.html','/store-admin.html','/super-admin.html','/trustai-dashboard.html','/dashboard.html','/app.html','/admin-ambassador-applications.html'];
  if (appPaths.includes(location.pathname) || (document.body && document.body.classList.contains('no-global-header'))) return;
  if (document.querySelector('.ta-global-header')) return;
  const style = document.createElement('style');
  style.textContent = `
    .ta-global-header{position:sticky;top:0;z-index:100;background:rgba(11,18,32,.96);backdrop-filter:blur(8px);border-bottom:1px solid rgba(148,163,184,.12)}
    .ta-header-wrap{max-width:1200px;margin:0 auto;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px}
    .ta-header-brand{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:20px;letter-spacing:-.02em;text-decoration:none}
    .ta-header-brand img{height:32px;width:auto}
    .ta-header-brand-text span{color:#60a5fa}
    .ta-header-nav{display:flex;align-items:center;gap:6px}
    .ta-header-nav a{color:#cbd5e1;text-decoration:none;font-size:14px;padding:8px 12px;border-radius:8px;font-weight:500}
    .ta-header-nav a:hover{color:#fff;background:rgba(148,163,184,.08)}
    .ta-header-cta{padding:8px 14px;border-radius:8px;background:#2563eb;color:#fff !important;font-weight:600;font-size:13px}
    .ta-header-cta:hover{background:#1d4ed8 !important}
    .ta-header-lang{display:flex;gap:4px;margin-left:6px;border-left:1px solid rgba(148,163,184,.2);padding-left:10px}
    .ta-header-lang button{background:transparent;border:1px solid transparent;color:#cbd5e1;padding:6px 10px;border-radius:6px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:4px;font-family:inherit}
    .ta-header-lang button:hover{background:rgba(148,163,184,.12);color:#fff}
    .ta-header-lang button.active{background:#2563eb;color:#fff;border-color:#2563eb}
    .ta-header-burger{display:none;background:transparent;border:0;color:#fff;cursor:pointer;font-size:24px;padding:6px 10px;line-height:1}
    @media (max-width:880px){
      .ta-header-burger{display:block}
      .ta-header-nav{display:none;position:absolute;top:100%;left:0;right:0;flex-direction:column;background:#0b1220;border-bottom:1px solid rgba(148,163,184,.18);padding:14px 20px 18px;gap:6px;align-items:stretch}
      .ta-header-nav.open{display:flex}
      .ta-header-nav a{padding:12px 14px;text-align:center}
      .ta-header-cta{text-align:center}
      .ta-header-lang{margin-left:0;border-left:0;border-top:1px solid rgba(148,163,184,.2);padding:12px 0 0;justify-content:center;margin-top:4px}
    }
  `;
  document.head.appendChild(style);

  const T = {
    en: { home:'Home', recruitment:'Recruitment', realestate:'Real Estate', sales:'Sales & Leads', ecommerce:'Ecommerce', login:'Log in', register:'Become ambassador' },
    no: { home:'Hjem', recruitment:'Rekruttering', realestate:'Eiendom', sales:'Salg & Leads', ecommerce:'E-handel', login:'Logg inn', register:'Bli ambassadør' },
  };

  function getLang(){
    return localStorage.getItem('trustai_lang') || 'no';
  }

  function applyLang(lang){
    document.documentElement.lang = lang;
    document.querySelectorAll('[data-no]').forEach(el => {
      const t = el.getAttribute('data-' + lang);
      if(t !== null) el.textContent = t;
    });
  }

  function setLang(lang){
    localStorage.setItem('trustai_lang', lang);
    applyLang(lang);
    cleanupLegacyHeaders();
  render();
    document.dispatchEvent(new CustomEvent('trustai:langchange', { detail: { lang } }));
  }


  function cleanupLegacyHeaders(){
    document.querySelectorAll('header.nav, header.sticky.glass, header.sticky.top-0, header:not(.ta-global-header)').forEach((h)=>{
      if (h.closest('.app') || h.closest('[data-dashboard]')) return;
      const hasNav = h.querySelector('.nav-cta, .btn, a[href*="login"], a[href*="signup"], a[href*="register"], a[href*="demo"]');
      if (hasNav) h.remove();
    });
  }

  function render() {
    const lang = getLang();
    const t = T[lang] || T.no;
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
        <button class="ta-header-burger" aria-label="Menu" type="button">☰</button>
        <nav class="ta-header-nav">
          <a href="/">${t.home}</a>
          <a href="/trustai_recruitment.html">${t.recruitment}</a>
          <a href="/trustai-real-estate.html">${t.realestate}</a>
          <a href="/sales-lead.html">${t.sales}</a>
          <a href="/ecommerce.html">${t.ecommerce}</a>
          <a href="/login.html">${t.login}</a>
          <a href="/ambassador-signup.html" class="ta-header-cta">${t.register}</a>
          <div class="ta-header-lang">
            <button data-lang="no" class="${lang==='no'?'active':''}" type="button">🇳🇴 NO</button>
            <button data-lang="en" class="${lang==='en'?'active':''}" type="button">🇬🇧 EN</button>
          </div>
        </nav>
      </div>`;
    const burger = header.querySelector('.ta-header-burger');
    const nav = header.querySelector('.ta-header-nav');
    burger.onclick = (e) => { e.stopPropagation(); nav.classList.toggle('open'); };
    header.querySelectorAll('.ta-header-lang button').forEach(b => {
      b.onclick = (e) => { e.stopPropagation(); setLang(b.dataset.lang); };
    });
  }

  render();
  applyLang(getLang());
  document.addEventListener('DOMContentLoaded', () => applyLang(getLang()));
  window.addEventListener('load', () => applyLang(getLang()));
})();

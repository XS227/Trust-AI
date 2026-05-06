(function () {
  if (document.querySelector('header.site-header') || document.querySelector('.ta-global-header')) return;
  if (document.body && document.body.classList.contains('no-global-header')) return;
  const style = document.createElement('style');
  style.textContent = `
    .ta-global-header{position:sticky;top:0;z-index:100;background:rgba(11,18,32,.96);backdrop-filter:blur(8px);border-bottom:1px solid rgba(148,163,184,.12)}
    .ta-header-wrap{max-width:1200px;margin:0 auto;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:14px}
    .ta-header-brand{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:20px;letter-spacing:-.02em;text-decoration:none}
    .ta-header-brand img{height:32px;width:auto}
    .ta-header-brand-text span{color:#60a5fa}
    .ta-header-nav{display:flex;align-items:center;gap:6px;position:relative}
    .ta-header-nav a{color:#cbd5e1;text-decoration:none;font-size:14px;padding:8px 12px;border-radius:8px;font-weight:500}
    .ta-header-nav a:hover{color:#fff;background:rgba(148,163,184,.08)}
    .ta-header-cta{padding:8px 14px;border-radius:8px;background:#2563eb;color:#fff !important;font-weight:600;font-size:13px}
    .ta-header-cta:hover{background:#1d4ed8 !important}
    .ta-dropdown{position:relative}
    .ta-dropdown-btn{display:inline-flex;align-items:center;gap:6px;color:#cbd5e1;background:transparent;border:0;font-size:14px;font-weight:500;padding:8px 12px;border-radius:8px;cursor:pointer;font-family:inherit}
    .ta-dropdown-btn:hover{color:#fff;background:rgba(148,163,184,.08)}
    .ta-dropdown-btn svg{width:12px;height:12px;transition:transform .15s}
    .ta-dropdown.open .ta-dropdown-btn svg{transform:rotate(180deg)}
    .ta-dropdown-menu{display:none;position:absolute;top:calc(100% + 6px);left:0;background:#0b1220;border:1px solid rgba(148,163,184,.18);border-radius:10px;padding:8px;min-width:220px;box-shadow:0 16px 40px rgba(0,0,0,.4);z-index:200}
    .ta-dropdown.open .ta-dropdown-menu{display:block}
    .ta-dropdown-menu a{display:flex;align-items:center;gap:10px;padding:10px 12px;color:#cbd5e1;text-decoration:none;border-radius:8px;font-size:14px}
    .ta-dropdown-menu a:hover{background:rgba(148,163,184,.08);color:#fff}
    .ta-dropdown-menu a .ico{font-size:16px}
    .ta-header-lang{margin-left:6px;border-left:1px solid rgba(148,163,184,.2);padding-left:10px}
    .ta-header-lang button{background:transparent;border:1px solid rgba(148,163,184,.18);color:#cbd5e1;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:5px;font-family:inherit;font-weight:600;transition:.15s}
    .ta-header-lang button:hover{background:rgba(148,163,184,.12);color:#fff;border-color:rgba(148,163,184,.4)}
    .ta-header-burger{display:none;background:transparent;border:0;color:#fff;cursor:pointer;font-size:24px;padding:6px 10px;line-height:1}
    @media (max-width:880px){
      .ta-header-burger{display:block}
      .ta-header-nav{display:none;position:absolute;top:100%;left:0;right:0;flex-direction:column;background:#0b1220;border-bottom:1px solid rgba(148,163,184,.18);padding:14px 20px 18px;gap:6px;align-items:stretch}
      .ta-header-nav.open{display:flex}
      .ta-header-nav a{padding:12px 14px;text-align:center}
      .ta-header-cta{text-align:center}
      .ta-dropdown-btn{justify-content:center;width:100%}
      .ta-dropdown-menu{position:static;width:100%;border:0;background:rgba(148,163,184,.05);box-shadow:none;margin-top:4px}
      .ta-header-lang{margin-left:0;border-left:0;border-top:1px solid rgba(148,163,184,.2);padding:12px 0 0;text-align:center;margin-top:4px}
      .ta-header-lang button{width:100%;justify-content:center}
    }
  `;
  document.head.appendChild(style);
  const T = {
    en: { useCases:'Use cases', recruitment:'Recruitment', recruitmentSub:'HR & talent agencies', realestate:'Real Estate', realestateSub:'International property', sales:'Sales & Leads', salesSub:'B2B partners', ecommerce:'Ecommerce', ecommerceSub:'Online stores', login:'Log in', register:'Sign up', switchTo:'Switch to Norwegian' },
    no: { useCases:'Bruksområder', recruitment:'Rekruttering', recruitmentSub:'HR & talent', realestate:'Eiendom', realestateSub:'Internasjonale prosjekter', sales:'Salg & Leads', salesSub:'B2B-partnere', ecommerce:'E-handel', ecommerceSub:'Nettbutikker', login:'Logg inn', register:'Registrer', switchTo:'Switch til engelsk' },
  };
  function getLang(){ return localStorage.getItem('trustai_lang') || 'no'; }
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
    render();
    document.dispatchEvent(new CustomEvent('trustai:langchange', { detail: { lang } }));
  }
  function render() {
    const lang = getLang();
    const t = T[lang] || T.no;
    const otherLang = lang === 'no' ? 'en' : 'no';
    const otherFlag = otherLang === 'no' ? '🇳🇴 NO' : '🇬🇧 EN';
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
          <div class="ta-dropdown" id="taUseCases">
            <button class="ta-dropdown-btn" type="button">${t.useCases}
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="ta-dropdown-menu">
              <a href="/trustai_recruitment.html"><span class="ico">👥</span><div><div style="font-weight:600;color:#fff">${t.recruitment}</div><div style="font-size:11px;color:#94a3b8">${t.recruitmentSub}</div></div></a>
              <a href="/trustai-real-estate.html"><span class="ico">🏖️</span><div><div style="font-weight:600;color:#fff">${t.realestate}</div><div style="font-size:11px;color:#94a3b8">${t.realestateSub}</div></div></a>
              <a href="/sales-lead.html"><span class="ico">💼</span><div><div style="font-weight:600;color:#fff">${t.sales}</div><div style="font-size:11px;color:#94a3b8">${t.salesSub}</div></div></a>
              <a href="/ecommerce.html"><span class="ico">🛒</span><div><div style="font-weight:600;color:#fff">${t.ecommerce}</div><div style="font-size:11px;color:#94a3b8">${t.ecommerceSub}</div></div></a>
            </div>
          </div>
          <a href="/login.html">${t.login}</a>
          <a href="/ambassador-signup.html" class="ta-header-cta">${t.register}</a>
          <div class="ta-header-lang">
            <button data-lang="${otherLang}" type="button" title="${t.switchTo}">${otherFlag}</button>
          </div>
        </nav>
      </div>`;
    const burger = header.querySelector('.ta-header-burger');
    const nav = header.querySelector('.ta-header-nav');
    burger.onclick = (e) => { e.stopPropagation(); nav.classList.toggle('open'); };
    const drop = header.querySelector('#taUseCases');
    const dropBtn = drop.querySelector('.ta-dropdown-btn');
    dropBtn.onclick = (e) => { e.stopPropagation(); drop.classList.toggle('open'); };
    document.addEventListener('click', (e) => { if(!drop.contains(e.target)) drop.classList.remove('open'); });
    header.querySelectorAll('.ta-header-lang button').forEach(b => {
      b.onclick = (e) => { e.stopPropagation(); setLang(b.dataset.lang); };
    });
  }
  render();
  applyLang(getLang());
  document.addEventListener('DOMContentLoaded', () => applyLang(getLang()));
  window.addEventListener('load', () => applyLang(getLang()));
})();

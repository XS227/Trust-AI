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
    .ta-header-nav{display:flex;align-items:center;gap:6px;position:relative}
    .ta-header-nav a{color:#cbd5e1;text-decoration:none;font-size:14px;padding:8px 12px;border-radius:8px;font-weight:500}
    .ta-header-nav a:hover{color:#fff;background:rgba(148,163,184,.08)}
    .ta-header-cta{padding:10px 18px;border-radius:10px;background:#ff5b24;color:#fff !important;font-weight:700;font-size:14px;border:0;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:8px;line-height:1;letter-spacing:.01em;box-shadow:0 6px 18px rgba(255,91,36,.28);transition:background .15s, transform .15s}
    .ta-header-cta:hover{background:#e64f1f !important;transform:translateY(-1px)}
    .ta-vipps-mark{display:inline-flex;align-items:center;justify-content:center;background:#fff;color:#ff5b24;border-radius:6px;font-weight:900;font-size:11px;padding:2px 6px;letter-spacing:.04em}
    /* modal */
    .ta-modal-backdrop{position:fixed;inset:0;background:rgba(7,11,22,.72);backdrop-filter:blur(4px);z-index:9999;display:none;align-items:center;justify-content:center;padding:20px}
    .ta-modal-backdrop.open{display:flex}
    .ta-modal{width:100%;max-width:440px;background:#0b1220;color:#e2e8f0;border:1px solid rgba(148,163,184,.18);border-radius:18px;padding:26px;box-shadow:0 30px 60px rgba(0,0,0,.5);position:relative}
    .ta-modal h2{margin:0 0 10px;font-size:20px;line-height:1.25;letter-spacing:-.01em}
    .ta-modal p{margin:0 0 18px;color:#94a3b8;font-size:14px;line-height:1.55}
    .ta-modal-close{position:absolute;top:12px;right:14px;background:transparent;border:0;color:#94a3b8;font-size:24px;line-height:1;cursor:pointer;padding:6px;border-radius:6px}
    .ta-modal-close:hover{color:#fff;background:rgba(148,163,184,.1)}
    .ta-modal-cta{width:100%;padding:14px;border-radius:12px;background:#ff5b24;color:#fff;border:0;font-weight:700;font-size:15px;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;justify-content:center;gap:10px}
    .ta-modal-cta:hover{background:#e64f1f}
    .ta-modal-meta{margin-top:12px;font-size:12px;color:#64748b;text-align:center}
    @media (max-width:560px){
      .ta-modal{border-radius:14px;padding:22px}
    }
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
    .ta-header-lang{margin-left:6px;border-left:1px solid rgba(148,163,184,.2);padding-left:10px;display:flex;align-items:center;gap:2px}
    .ta-lang-btn{background:transparent;border:1px solid transparent;color:#94a3b8;padding:5px 9px;border-radius:6px;cursor:pointer;font-size:12px;font-family:inherit;font-weight:700;letter-spacing:.05em;transition:.15s}
    .ta-lang-btn:hover{color:#fff;background:rgba(148,163,184,.1)}
    .ta-lang-btn.active{color:#fff;background:rgba(148,163,184,.18);border-color:rgba(148,163,184,.35)}
    .ta-header-burger{display:none;background:transparent;border:0;color:#fff;cursor:pointer;font-size:24px;padding:6px 10px;line-height:1}
    @media (max-width:880px){
      .ta-header-burger{display:block}
      .ta-header-nav{display:none;position:absolute;top:100%;left:0;right:0;flex-direction:column;background:#0b1220;border-bottom:1px solid rgba(148,163,184,.18);padding:14px 20px 18px;gap:6px;align-items:stretch}
      .ta-header-nav.open{display:flex}
      .ta-header-nav a{padding:12px 14px;text-align:center}
      .ta-header-cta{text-align:center}
      .ta-dropdown-btn{justify-content:center;width:100%}
      .ta-dropdown-menu{position:static;width:100%;border:0;background:rgba(148,163,184,.05);box-shadow:none;margin-top:4px}
      .ta-header-lang{margin-left:0;border-left:0;border-top:1px solid rgba(148,163,184,.2);padding:12px 0 0;text-align:center;margin-top:4px;justify-content:center}
      .ta-lang-btn{flex:1}
    }
  `;
  document.head.appendChild(style);
  const T = {
    en: { useCases:'Use cases', recruitment:'Recruitment', recruitmentSub:'HR & talent agencies', realestate:'Real Estate', realestateSub:'International property', sales:'Sales & Leads', salesSub:'B2B partners', ecommerce:'Ecommerce', ecommerceSub:'Online stores', switchTo:'Switch to Norwegian', vippsCta:'Continue with Vipps', modalTitle:'Continue with Vipps', modalBody:'Use Vipps for secure sign-in and registration. If you already have an account we will send you straight to your dashboard. If not, we will help you complete the registration.', modalCta:'Continue with Vipps', modalMeta:'You must be 18 or older to register.' },
    no: { useCases:'Bruksområder', recruitment:'Rekruttering', recruitmentSub:'HR & talent', realestate:'Eiendom', realestateSub:'Internasjonale prosjekter', sales:'Salg & Leads', salesSub:'B2B-partnere', ecommerce:'E-handel', ecommerceSub:'Nettbutikker', switchTo:'Switch til engelsk', vippsCta:'Fortsett med Vipps', modalTitle:'Fortsett med Vipps', modalBody:'Bruk Vipps for trygg innlogging og registrering. Hvis du allerede har konto sender vi deg rett til dashboardet ditt. Hvis ikke hjelper vi deg å fullføre registreringen.', modalCta:'Fortsett med Vipps', modalMeta:'Du må være 18 år eller eldre for å registrere deg.' },
  };
  function getLang(){
    const stored = localStorage.getItem('trustai_lang');
    if (stored) return stored;
    const nav = navigator.language || navigator.userLanguage || '';
    return (nav.startsWith('no') || nav.startsWith('nb') || nav.startsWith('nn')) ? 'no' : 'en';
  }
  function applyLang(lang){
    document.documentElement.lang = lang;
    document.querySelectorAll('[data-no]').forEach(el => {
      const t = el.getAttribute('data-' + lang);
      if(t !== null) el.textContent = t;
    });
    // Sync button active states whenever language is applied
    document.querySelectorAll('.ta-lang-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.lang === lang);
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
    // lang buttons rendered below — no single-toggle needed
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
          <button type="button" class="ta-header-cta" id="taVippsCta">
            <span class="ta-vipps-mark">vipps</span><span>${t.vippsCta}</span>
          </button>
          <div class="ta-header-lang">
            <button class="ta-lang-btn${lang==='en'?' active':''}" data-lang="en" type="button">🇬🇧 EN</button>
            <button class="ta-lang-btn${lang==='no'?' active':''}" data-lang="no" type="button">🇳🇴 NO</button>
          </div>
        </nav>
      </div>`;
    // Vipps modal (rendered once outside the header so it overlays the page).
    let modal = document.querySelector('.ta-modal-backdrop');
    if (!modal) {
      modal = document.createElement('div');
      modal.className = 'ta-modal-backdrop';
      modal.setAttribute('role', 'dialog');
      modal.setAttribute('aria-modal', 'true');
      document.body.appendChild(modal);
    }
    modal.innerHTML = `
      <div class="ta-modal" role="document">
        <button class="ta-modal-close" type="button" aria-label="Lukk">×</button>
        <h2>${t.modalTitle}</h2>
        <p>${t.modalBody}</p>
        <button type="button" class="ta-modal-cta" id="taModalCta">
          <span class="ta-vipps-mark">vipps</span><span>${t.modalCta}</span>
        </button>
        <div class="ta-modal-meta">${t.modalMeta}</div>
      </div>`;
    function openModal() { modal.classList.add('open'); document.documentElement.style.overflow = 'hidden'; }
    function closeModal() { modal.classList.remove('open'); document.documentElement.style.overflow = ''; }
    header.querySelector('#taVippsCta').onclick = (e) => { e.stopPropagation(); openModal(); };
    modal.querySelector('.ta-modal-close').onclick = closeModal;
    modal.onclick = (e) => { if (e.target === modal) closeModal(); };
    modal.querySelector('#taModalCta').onclick = () => { window.location.href = '/api/auth/vipps/login.php?intent=auto'; };
    if (!window.__taVippsEscBound) {
      document.addEventListener('keydown', (e) => {
        const m = document.querySelector('.ta-modal-backdrop.open');
        if (e.key === 'Escape' && m) { m.classList.remove('open'); document.documentElement.style.overflow = ''; }
      });
      window.__taVippsEscBound = true;
    }
    const burger = header.querySelector('.ta-header-burger');
    const nav = header.querySelector('.ta-header-nav');
    burger.onclick = (e) => { e.stopPropagation(); nav.classList.toggle('open'); };
    const drop = header.querySelector('#taUseCases');
    const dropBtn = drop.querySelector('.ta-dropdown-btn');
    dropBtn.onclick = (e) => { e.stopPropagation(); drop.classList.toggle('open'); };
    document.addEventListener('click', (e) => { if(!drop.contains(e.target)) drop.classList.remove('open'); });
    header.querySelectorAll('.ta-lang-btn').forEach(b => {
      b.onclick = (e) => { e.stopPropagation(); setLang(b.dataset.lang); };
    });
  }
  render();
  applyLang(getLang());
  document.addEventListener('DOMContentLoaded', () => applyLang(getLang()));
  window.addEventListener('load', () => applyLang(getLang()));
})();

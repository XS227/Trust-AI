(function () {
  const appPaths=['/ambassador-dashboard.html','/store-admin.html','/super-admin.html','/trustai-dashboard.html','/dashboard.html','/app.html','/admin-ambassador-applications.html'];
  if (appPaths.includes(location.pathname) || document.querySelector('footer.ta-global-footer')) return;
  const style = document.createElement('style');
  style.textContent = `
    .ta-global-footer{background:#0b1020;color:#cbd5e1;margin-top:60px}
    .ta-footer-wrap{max-width:1200px;margin:0 auto;padding:52px 20px 26px}
    .ta-footer-top{display:grid;grid-template-columns:1.5fr 1fr 1fr 1fr;gap:36px}
    .ta-footer-brand-wrap{display:flex;flex-direction:column;gap:12px}
    .ta-footer-brand{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:22px;letter-spacing:-.02em;text-decoration:none}
    .ta-footer-brand img{height:38px;width:auto}
    .ta-footer-tagline{color:#94a3b8;font-size:13px;line-height:1.6;max-width:280px;margin:0}
    .ta-footer-contact{display:flex;flex-direction:column;gap:4px;margin-top:4px}
    .ta-footer-contact a{display:inline-flex;align-items:center;gap:7px;color:#94a3b8;font-size:13px;text-decoration:none;padding:4px 0;transition:color .15s}
    .ta-footer-contact a:hover{color:#60a5fa}
    .ta-footer-contact svg{width:13px;height:13px;flex-shrink:0;opacity:.7}
    .ta-footer-col h4{color:#fff;font-size:11px;margin:0 0 14px;font-weight:700;letter-spacing:.8px;text-transform:uppercase}
    .ta-footer-col a{display:block;color:#94a3b8;text-decoration:none;margin:9px 0;font-size:13px;transition:color .15s}
    .ta-footer-col a:hover{color:#fff}
    .ta-footer-col .ta-footer-vipps{display:inline-flex;align-items:center;gap:6px;background:#ff5b24;color:#fff !important;padding:8px 14px;border-radius:8px;font-weight:700;font-size:12px;margin-top:2px;transition:background .15s}
    .ta-footer-col .ta-footer-vipps:hover{background:#e64f1f}
    .ta-footer-vipps-mark{background:rgba(255,255,255,.22);color:#fff;font-size:10px;font-weight:900;padding:1px 5px;border-radius:4px;letter-spacing:.04em}
    .ta-footer-bottom{border-top:1px solid rgba(148,163,184,.1);margin-top:38px;padding-top:20px;font-size:12px;color:#64748b;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
    .ta-footer-bottom a{color:#64748b;text-decoration:none}
    .ta-footer-bottom a:hover{color:#94a3b8}
    .ta-footer-beta{display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#475569}
    .ta-footer-beta::before{content:'';width:6px;height:6px;border-radius:50%;background:#10b981;flex-shrink:0}
    @media (max-width:880px){.ta-footer-top{grid-template-columns:1fr 1fr;gap:28px}.ta-footer-brand-wrap{grid-column:1/-1}}
    @media (max-width:540px){.ta-footer-top{grid-template-columns:1fr}.ta-footer-bottom{flex-direction:column;align-items:flex-start}}
  `;
  document.head.appendChild(style);
  const T = {
    en: {
      tagline: 'Referral infrastructure for modern merchants.',
      platform: 'Platform', home: 'Home', ecommerce: 'Ecommerce',
      recruitment: 'Recruitment', sales: 'Sales & Leads', howItWorks: 'How it works',
      access: 'Access', vipps: 'Continue with Vipps', becomeAmb: 'Become an ambassador',
      merchantOnboarding: 'Merchant onboarding',
      company: 'Company', about: 'About TrustAI', contact: 'Contact',
      privacy: 'Privacy', terms: 'Terms',
      rights: 'All rights reserved.',
      beta: 'Operational Beta'
    },
    no: {
      tagline: 'Referral-infrastruktur for moderne butikker.',
      platform: 'Plattform', home: 'Hjem', ecommerce: 'E-handel',
      recruitment: 'Rekruttering', sales: 'Salg & Leads', howItWorks: 'Hvordan det fungerer',
      access: 'Tilgang', vipps: 'Fortsett med Vipps', becomeAmb: 'Bli ambassadør',
      merchantOnboarding: 'Butikk-onboarding',
      company: 'Selskap', about: 'Om TrustAI', contact: 'Kontakt',
      privacy: 'Personvern', terms: 'Vilkår',
      rights: 'Alle rettigheter forbeholdt.',
      beta: 'Operativ beta'
    }
  };
  function getLang() {
    const stored = localStorage.getItem('trustai_lang');
    if (stored) return stored;
    const nav = navigator.language || navigator.userLanguage || '';
    return (nav.startsWith('no') || nav.startsWith('nb') || nav.startsWith('nn')) ? 'no' : 'en';
  }
  function render() {
    const lang = getLang();
    const t = T[lang] || T.en;
    const year = new Date().getFullYear();
    let footer = document.querySelector('.ta-global-footer');
    if (!footer) {
      footer = document.createElement('footer');
      footer.className = 'ta-global-footer';
      document.body.appendChild(footer);
    }
    footer.innerHTML = `
      <div class="ta-footer-wrap">
        <div class="ta-footer-top">
          <div class="ta-footer-brand-wrap">
            <a href="/" class="ta-footer-brand">
              <img src="/logo.png" alt="TrustAi">
              TrustAi
            </a>
            <p class="ta-footer-tagline">${t.tagline}</p>
            <div class="ta-footer-contact">
              <a href="mailto:hello@trustai.no">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                hello@trustai.no
              </a>
              <a href="tel:+4741227175">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 1.27h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.77-.77a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 21.73 16.92z"/></svg>
                +47 412 27 175
              </a>
            </div>
          </div>
          <div class="ta-footer-col">
            <h4>${t.platform}</h4>
            <a href="/">${t.home}</a>
            <a href="/ecommerce.html">${t.ecommerce}</a>
            <a href="/trustai_recruitment.html">${t.recruitment}</a>
            <a href="/sales-lead.html">${t.sales}</a>
            <a href="/how-it-works.html">${t.howItWorks}</a>
          </div>
          <div class="ta-footer-col">
            <h4>${t.access}</h4>
            <a href="/api/auth/vipps/login.php?intent=auto" class="ta-footer-vipps"><span class="ta-footer-vipps-mark">vipps</span>${t.vipps}</a>
            <a href="/ambassador-signup.html">${t.becomeAmb}</a>
            <a href="/store-signup.html">${t.merchantOnboarding}</a>
          </div>
          <div class="ta-footer-col">
            <h4>${t.company}</h4>
            <a href="/">${t.about}</a>
            <a href="mailto:hello@trustai.no">${t.contact}</a>
            <a href="/personvern.html">${t.privacy}</a>
            <a href="/vilkar.html">${t.terms}</a>
          </div>
        </div>
        <div class="ta-footer-bottom">
          <div>© ${year} TrustAI · ${t.rights}</div>
          <span class="ta-footer-beta">${t.beta}</span>
        </div>
      </div>
    `;
  }
  render();
  document.addEventListener('trustai:langchange', render);
})();

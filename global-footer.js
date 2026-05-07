(function () {
  const appPaths=['/ambassador-dashboard.html','/store-admin.html','/super-admin.html','/trustai-dashboard.html','/dashboard.html','/app.html','/admin-ambassador-applications.html'];
  if (appPaths.includes(location.pathname) || document.querySelector('footer.ta-global-footer')) return;
  const style = document.createElement('style');
  style.textContent = `
    .ta-global-footer{background:#0b1020;color:#cbd5e1;margin-top:60px}
    .ta-footer-wrap{max-width:1200px;margin:0 auto;padding:56px 20px 28px}
    .ta-footer-top{display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr 1fr;gap:36px}
    .ta-footer-brand-wrap{display:flex;flex-direction:column;gap:14px}
    .ta-footer-brand{display:flex;align-items:center;gap:12px;color:#fff;font-weight:800;font-size:30px;letter-spacing:-.02em;text-decoration:none}
    .ta-footer-brand img{height:48px;width:auto}
    .ta-footer-brand span span{color:#60a5fa}
    .ta-footer-tagline{color:#94a3b8;font-size:13px;line-height:1.6;max-width:300px}
    .ta-footer-company{font-size:12px;color:#64748b;line-height:1.7;margin-top:6px}
    .ta-footer-company b{color:#cbd5e1;display:block;margin-bottom:2px;font-weight:600}
    .ta-footer-contact{display:flex;flex-direction:column;gap:6px;margin-top:8px}
    .ta-footer-contact a{display:inline-flex;align-items:center;gap:6px;color:#cbd5e1;font-size:13px;text-decoration:none;padding:6px 0}
    .ta-footer-contact a:hover{color:#60a5fa}
    .ta-footer-contact svg{width:14px;height:14px;flex-shrink:0}
    .ta-footer-col h4{color:#fff;font-size:12px;margin:0 0 16px;font-weight:700;letter-spacing:.6px;text-transform:uppercase}
    .ta-footer-col a{display:block;color:#94a3b8;text-decoration:none;margin:10px 0;font-size:13px;transition:color .15s}
    .ta-footer-col a:hover{color:#fff}
    .ta-footer-bottom{border-top:1px solid rgba(148,163,184,.12);margin-top:42px;padding-top:22px;font-size:13px;color:#64748b;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px}
    .ta-footer-bottom a{color:#94a3b8;text-decoration:none}
    .ta-footer-bottom a:hover{color:#fff}
    .ta-footer-credit{font-size:12px;color:#64748b}
    .ta-footer-credit a{color:#94a3b8}
    @media (max-width:980px){.ta-footer-top{grid-template-columns:1.2fr repeat(4,minmax(120px,1fr));gap:20px}}
    @media (max-width:760px){.ta-footer-top{grid-template-columns:1fr 1fr;gap:24px}.ta-footer-brand-wrap{grid-column:1/-1}}
    @media (max-width:560px){.ta-footer-bottom{flex-direction:column;align-items:flex-start}}
  `;
  document.head.appendChild(style);
  const T = {
    no: { tagline:'Referral-infrastruktur for alle nettverk. Spor anbefalinger, beløn resultater.', solutions:'Løsninger', recruitment:'Rekruttering', realestate:'Eiendom', sales:'Salg & Leads', ecommerce:'E-handel', ambassadors:'For ambassadører', becomeAmb:'Bli ambassadør', login:'Logg inn', faq:'FAQ', howItWorks:'Hvordan det fungerer', company:'Selskap', about:'Om TrustAI', contact:'Kontakt', legal:'Juridisk', terms:'Vilkår', privacy:'Personvern', rights:'Alle rettigheter forbeholdt.', builtBy:'Bygget av' },
    en: { tagline:'Referral infrastructure for every network. Track recommendations, reward results.', solutions:'Solutions', recruitment:'Recruitment', realestate:'Real Estate', sales:'Sales & Leads', ecommerce:'Ecommerce', ambassadors:'For ambassadors', becomeAmb:'Become ambassador', login:'Log in', faq:'FAQ', howItWorks:'How it works', company:'Company', about:'About TrustAI', contact:'Contact', legal:'Legal', terms:'Terms', privacy:'Privacy', rights:'All rights reserved.', builtBy:'Built by' }
  };
  function getLang() { return localStorage.getItem('trustai_lang') || 'no'; }
  function render() {
    const lang = getLang();
    const t = T[lang] || T.no;
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
              <span>Trust<span>Ai</span></span>
            </a>
            <p class="ta-footer-tagline">${t.tagline}</p>
            <div class="ta-footer-contact">
              <a href="https://wa.me/4741227175">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                +47 412 27 175
              </a>
              <a href="mailto:ks@trustai.no">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                ks@trustai.no
              </a>
            </div>
          </div>
          <div class="ta-footer-col">
            <h4>${t.solutions}</h4>
            <a href="/trustai_recruitment.html">${t.recruitment}</a>
            <a href="/trustai-real-estate.html">${t.realestate}</a>
            <a href="/sales-lead.html">${t.sales}</a>
            <a href="/ecommerce.html">${t.ecommerce}</a>
          </div>
          <div class="ta-footer-col">
            <h4>${t.ambassadors}</h4>
            <a href="/ambassador-signup.html">${t.becomeAmb}</a>
            <a href="/login.html">${t.login}</a>
            <a href="/how-it-works.html">${t.howItWorks}</a>
          </div>
          <div class="ta-footer-col">
            <h4>${t.company}</h4>
            <a href="/">${t.about}</a>
            <a href="mailto:ks@trustai.no">${t.contact}</a>
            <a href="/how-it-works.html">${t.howItWorks}</a>
          </div>
          <div class="ta-footer-col">
            <h4>${t.legal}</h4>
            <a href="/vilkar.html">${t.terms}</a>
            <a href="/personvern.html">${t.privacy}</a>
          </div>
        </div>
        <div class="ta-footer-bottom">
          <div>© ${year} SETAEI · ${t.rights}</div>
          
        </div>
      </div>
    `;
  }
  render();
  document.addEventListener('trustai:langchange', render);
})();

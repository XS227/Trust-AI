(function () {
  const style = document.createElement('style');
  style.textContent = `
    .ta-global-footer{background:#0b1020;color:#cbd5e1;margin-top:48px}
    .ta-footer-wrap{max-width:1200px;margin:0 auto;padding:48px 20px 24px}
    .ta-footer-top{display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr 1fr;gap:32px}
    .ta-footer-brand-wrap{display:flex;flex-direction:column;gap:14px}
    .ta-footer-brand{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:24px;letter-spacing:-.02em;text-decoration:none}
    .ta-footer-mark{width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:grid;place-items:center;color:#fff;font-weight:900}
    .ta-footer-tagline{color:#94a3b8;font-size:13px;line-height:1.6;max-width:280px}
    .ta-footer-lang{display:flex;gap:8px;margin-top:6px}
    .ta-footer-lang button{background:#1e293b;border:1px solid #334155;color:#cbd5e1;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:6px}
    .ta-footer-lang button:hover{background:#334155;color:#fff}
    .ta-footer-lang button.active{background:#2563eb;border-color:#2563eb;color:#fff}
    .ta-footer-col h4{color:#fff;font-size:13px;margin:0 0 14px;font-weight:700;letter-spacing:.4px;text-transform:uppercase}
    .ta-footer-col a{display:block;color:#cbd5e1;text-decoration:none;margin:9px 0;font-size:14px}
    .ta-footer-col a:hover{color:#fff}
    .ta-footer-bottom{border-top:1px solid rgba(148,163,184,.15);margin-top:36px;padding-top:18px;font-size:13px;color:#94a3b8;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
    .ta-footer-bottom a{color:#cbd5e1;text-decoration:none}
    .ta-footer-bottom a:hover{color:#fff}
    .ta-footer-social{display:flex;gap:14px}
    .ta-footer-social a{font-size:13px}
    @media (max-width:980px){.ta-footer-top{grid-template-columns:1fr 1fr}.ta-footer-brand-wrap{grid-column:1/-1}}
    @media (max-width:560px){.ta-footer-top{grid-template-columns:1fr 1fr;gap:24px}.ta-footer-bottom{flex-direction:column;align-items:flex-start}}
  `;
  document.head.appendChild(style);

  // Translations
  const T = {
    en: {
      tagline: 'Referral infrastructure for every network. Track recommendations, reward results.',
      useCases: 'Use cases',
      ecommerce: 'Ecommerce & stores',
      recruitment: 'Recruitment & HR',
      sales: 'Sales & lead generation',
      product: 'Product',
      home: 'Home',
      howItWorks: 'How it works',
      pricing: 'Pricing',
      company: 'Company',
      contact: 'Contact',
      terms: 'Terms',
      privacy: 'Privacy',
      account: 'Account',
      login: 'Log in',
      signup: 'Become ambassador',
      copyright: '© 2026',
      rights: 'All rights reserved.',
      builtBy: 'Built by',
    },
    no: {
      tagline: 'Referral-infrastruktur for alle nettverk. Spor anbefalinger, beløn resultater.',
      useCases: 'Bruksområder',
      ecommerce: 'E-handel & butikker',
      recruitment: 'Rekruttering & HR',
      sales: 'Salg & leads',
      product: 'Produkt',
      home: 'Hjem',
      howItWorks: 'Hvordan det fungerer',
      pricing: 'Priser',
      company: 'Selskap',
      contact: 'Kontakt',
      terms: 'Vilkår',
      privacy: 'Personvern',
      account: 'Konto',
      login: 'Logg inn',
      signup: 'Bli ambassadør',
      copyright: '© 2026',
      rights: 'Alle rettigheter forbeholdt.',
      builtBy: 'Bygget av',
    }
  };

  function getLang() {
    return localStorage.getItem('trustai_lang') || (navigator.language || '').toLowerCase().startsWith('no') ? 'no' : 'en';
  }

  function setLang(lang) {
    localStorage.setItem('trustai_lang', lang);
    document.documentElement.lang = lang;
    render();
    // Trigger custom event for other components to react
    document.dispatchEvent(new CustomEvent('trustai:langchange', { detail: { lang } }));
  }

  function render() {
    const lang = localStorage.getItem('trustai_lang') || 'en';
    const t = T[lang] || T.en;
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
            <a href="/" class="ta-footer-brand" aria-label="TrustAI home">
              <span class="ta-footer-mark">T</span>
              <span>TrustAi</span>
            </a>
            <div class="ta-footer-tagline">${t.tagline}</div>
            <div class="ta-footer-lang">
              <button data-lang="en" class="${lang==='en'?'active':''}" type="button">🇬🇧 EN</button>
              <button data-lang="no" class="${lang==='no'?'active':''}" type="button">🇳🇴 NO</button>
            </div>
          </div>
          <div class="ta-footer-col">
            <h4>${t.useCases}</h4>
            <a href="/ecommerce.html">${t.ecommerce}</a>
            <a href="/trustai_recruitment.html">${t.recruitment}</a>
            <a href="/sales-lead.html">${t.sales}</a>
          </div>
          <div class="ta-footer-col">
            <h4>${t.product}</h4>
            <a href="/">${t.home}</a>
            <a href="/how-it-works.html">${t.howItWorks}</a>
            <a href="/login.html">${t.login}</a>
          </div>
          <div class="ta-footer-col">
            <h4>${t.company}</h4>
            <a href="https://setaei.com" target="_blank" rel="noopener">${t.contact}</a>
            <a href="/vilkar.html">${t.terms}</a>
            <a href="/personvern.html">${t.privacy}</a>
          </div>
          <div class="ta-footer-col">
            <h4>${t.account}</h4>
            <a href="/login.html">${t.login}</a>
            <a href="/ambassador-signup.html">${t.signup}</a>
          </div>
        </div>
        <div class="ta-footer-bottom">
          <div>${t.copyright} <a href="https://setaei.com" target="_blank" rel="noopener">SETAEI</a>. ${t.rights}</div>
          <div>${t.builtBy} <a href="https://setaei.com" target="_blank" rel="noopener">Khabat Setaei</a></div>
        </div>
      </div>`;
    footer.querySelectorAll('.ta-footer-lang button').forEach(b => {
      b.onclick = () => setLang(b.dataset.lang);
    });
  }

  // Initial language detection
  if (!localStorage.getItem('trustai_lang')) {
    const browserLang = (navigator.language || '').toLowerCase();
    localStorage.setItem('trustai_lang', browserLang.startsWith('no') ? 'no' : 'en');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', render);
  } else {
    render();
  }
})();

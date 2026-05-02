(function () {
  const style = document.createElement('style');
  style.textContent = `
    .ta-global-footer{background:#0b1020;color:#cbd5e1;margin-top:48px}
    .ta-footer-wrap{max-width:1200px;margin:0 auto;padding:44px 20px 24px}
    .ta-footer-top{display:grid;grid-template-columns:1.2fr repeat(4,1fr);gap:28px}
    .ta-footer-brand{display:flex;align-items:center;gap:10px;color:#fff;font-weight:800;font-size:24px;letter-spacing:-.02em}
    .ta-footer-mark{width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:grid;place-items:center;color:#fff;font-weight:900}
    .ta-footer-col h4{color:#fff;font-size:14px;margin:0 0 12px;font-weight:700}
    .ta-footer-col a{display:block;color:#cbd5e1;text-decoration:none;margin:8px 0;font-size:14px}
    .ta-footer-col a:hover{color:#fff}
    .ta-footer-bottom{border-top:1px solid rgba(148,163,184,.2);margin-top:24px;padding-top:16px;font-size:13px;color:#94a3b8}
    .ta-footer-bottom a{color:#cbd5e1;text-decoration:none}
    @media (max-width:980px){.ta-footer-top{grid-template-columns:1fr 1fr}.ta-footer-brand-wrap{grid-column:1/-1}}
    @media (max-width:620px){.ta-footer-top{grid-template-columns:1fr}}
  `;
  document.head.appendChild(style);

  const footer = document.createElement('footer');
  footer.className = 'ta-global-footer';
  footer.innerHTML = `
    <div class="ta-footer-wrap">
      <div class="ta-footer-top">
        <div class="ta-footer-brand-wrap">
          <a href="/" class="ta-footer-brand" aria-label="TrustAi home">
            <span class="ta-footer-mark">T</span>
            <span>TrustAi</span>
          </a>
        </div>
        <div class="ta-footer-col">
          <h4>Bruksområder</h4>
          <a href="/trustai_ecommerce.html">Ecommerce & stores</a>
          <a href="/trustai_recruitment.html">Recruitment & HR</a>
          <a href="/trustai_sales.html">Sales & lead generation</a>
        </div>
        <div class="ta-footer-col">
          <h4>Produkt</h4>
          <a href="/">Home</a>
          <a href="/demo.html">Demo</a>
          <a href="/support.html">Support</a>
        </div>
        <div class="ta-footer-col">
          <h4>Selskap</h4>
          <a href="/contact.html">Kontakt</a>
          <a href="/terms.html">Vilkår</a>
          <a href="/privacy.html">Personvern</a>
        </div>
        <div class="ta-footer-col">
          <h4>Konto</h4>
          <a href="/login.html">Login</a>
          <a href="/register.html">Register</a>
        </div>
      </div>
      <div class="ta-footer-bottom">© 2026 <a href="https://setai.no" target="_blank" rel="noopener">SETAEI</a></div>
    </div>`;

  document.body.appendChild(footer);
})();

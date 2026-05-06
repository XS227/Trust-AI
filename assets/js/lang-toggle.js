/* Single-flag language toggle - shows opposite language */
(function () {
  function getLang(){ return localStorage.getItem('trustai_lang') || 'en'; }
  function setLang(lang){
    localStorage.setItem('trustai_lang', lang);
    document.documentElement.lang = lang;
    location.reload();
  }
  function render() {
    var current = getLang();
    var other = current === 'en' ? 'no' : 'en';
    var label = other === 'no' ? '🇳🇴 NO' : '🇬🇧 EN';
    var title = other === 'no' ? 'Bytt til norsk' : 'Switch to English';
    document.querySelectorAll('[data-lang-toggle]').forEach(function(el){
      el.innerHTML = '<button type="button" title="'+title+'" style="background:transparent;border:1px solid currentColor;color:inherit;padding:6px 12px;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;opacity:.85" onclick="window.__taiSetLang(\''+other+'\')">'+label+'</button>';
    });
  }
  window.__taiSetLang = setLang;
  if (!localStorage.getItem('trustai_lang')) {
    var b = (navigator.language || '').toLowerCase();
    localStorage.setItem('trustai_lang', b.startsWith('no') ? 'no' : 'en');
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', render);
  else render();
})();

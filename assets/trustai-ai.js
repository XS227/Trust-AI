/* TrustAI Shopify referral tracking
 * Plukker opp ?trustai_ref= fra URL og lagrer som cart attribute
 * slik at koden følger med ordren via webhook (note_attributes.trustai_ref)
 */
(function () {
  'use strict';
  var STORAGE_KEY = 'trustai_ref';
  var COOKIE_DAYS = 30;

  function getQueryParam(name) {
    try {
      var p = new URLSearchParams(window.location.search);
      return p.get(name) || '';
    } catch (e) { return ''; }
  }

  function setCookie(name, value, days) {
    var d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
  }

  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : '';
  }

  function getStoredRef() {
    var ref = '';
    try { ref = window.localStorage.getItem(STORAGE_KEY) || ''; } catch (e) {}
    if (!ref) ref = getCookie(STORAGE_KEY);
    return ref;
  }

  function storeRef(ref) {
    if (!ref) return;
    try { window.localStorage.setItem(STORAGE_KEY, ref); } catch (e) {}
    setCookie(STORAGE_KEY, ref, COOKIE_DAYS);
  }

  function updateCart(ref) {
    if (!ref) return;
    // Send som cart attribute - dette kommer fram som note_attributes på ordren
    fetch('/cart/update.js', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ attributes: { trustai_ref: ref } })
    }).then(function (r) {
      if (r.ok) {
        console.log('[TrustAI] Referral code attached to cart:', ref);
      } else {
        console.warn('[TrustAI] Cart update failed:', r.status);
      }
    }).catch(function (err) {
      console.warn('[TrustAI] Cart update error:', err);
    });
  }

  function init() {
    // 1) Plukk opp fra URL hvis tilstede
    var urlRef = getQueryParam('trustai_ref');
    if (urlRef) {
      storeRef(urlRef);
    }

    // 2) Bruk lagret kode (URL eller tidligere besøk)
    var ref = getStoredRef();
    if (!ref) return;

    // 3) Lagre i cart slik at den følger med ordren
    updateCart(ref);

    // 4) Re-attach hver gang noe legges i kurven
    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (form && form.action && /\/cart\/add/.test(form.action)) {
        // Vent litt så cart faktisk oppdateres, så re-applyer attribute
        setTimeout(function () { updateCart(ref); }, 600);
      }
    }, true);

    // 5) Ekstra sikkerhet — re-applyer ved navigasjon til checkout
    document.addEventListener('click', function (e) {
      var t = e.target;
      while (t && t !== document.body) {
        if (t.tagName === 'A' && t.href && /\/checkout/.test(t.href)) {
          updateCart(ref);
          break;
        }
        t = t.parentNode;
      }
    }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

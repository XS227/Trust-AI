(function () {
  const REF_KEY = "trustai_ref";
  const REF_PATTERN = /^[A-Za-z0-9_-]{3,32}$/;
  const COOKIE_MAX_AGE = 60 * 60 * 24 * 30; // 30 days

  function isValidRef(value) {
    return REF_PATTERN.test(String(value || "").trim());
  }

  function setCookie(name, value, maxAge) {
    document.cookie = `${encodeURIComponent(name)}=${encodeURIComponent(value)}; path=/; max-age=${maxAge}; SameSite=Lax`;
  }

  function getCookie(name) {
    const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : "";
  }

  function readRefFromURL() {
    const params = new URLSearchParams(window.location.search);
    const ref = params.get("ref") || "";
    return isValidRef(ref) ? ref : "";
  }

  function getStoredRef() {
    const fromStorage = localStorage.getItem(REF_KEY) || "";
    if (isValidRef(fromStorage)) return fromStorage;

    const fromCookie = getCookie(REF_KEY);
    if (isValidRef(fromCookie)) return fromCookie;

    return "";
  }

  function persistRef(ref) {
    if (!isValidRef(ref)) return;

    // 🔥 viktig: lagre både i localStorage og cookie
    localStorage.setItem(REF_KEY, ref);
    setCookie(REF_KEY, ref, COOKIE_MAX_AGE);

    // sørg for at input-feltet finnes og heter attributes[trustai_ref]
    document.querySelectorAll('form[action*="/cart/add"]').forEach((form) => {
      let hidden = form.querySelector('input[name="attributes[trustai_ref]"]');
      if (!hidden) {
        hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = "attributes[trustai_ref]";
        form.appendChild(hidden);
      }
      hidden.value = ref;
    });

    // bruk FormData for å oppdatere handlekurven ved AJAX-kasser
    const fd = new FormData();
    fd.append("attributes[trustai_ref]", ref);
    fetch("/cart/update.js", {
      method: "POST",
      body: fd
    }).catch(() => {});
  }

  function isCartPage() {
    return /^\/cart(?:\/|$)/.test(window.location.pathname);
  }

  function bindCartAddHooks() {
    // før checkout eller ved add-to-cart
    document.addEventListener("click", (e) => {
      if (e.target.closest('form[action*="/cart/add"]') || e.target.closest(".shopify-payment-button")) {
        persistRef(localStorage.getItem(REF_KEY));
      }
    });
  }

  function exposeGlobal(ref) {
    // 🔥 gjør ref tilgjengelig globalt (for debugging / videre bruk)
    window.TrustAI = {
      getRef: () => ref
    };
  }

  function initializeTrustAIReferral() {
    const urlRef = readRefFromURL();
    const storedRef = getStoredRef();

    // 🔥 prioritet: URL → lagre → fallback storage
    const ref = urlRef || storedRef;
    if (!ref) return;

    persistRef(ref);
    bindCartAddHooks();

    if (isCartPage() || document.querySelector('form[action*="/cart/add"]')) {
      persistRef(ref);
    }

    exposeGlobal(ref);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeTrustAIReferral);
  } else {
    initializeTrustAIReferral();
  }
})();

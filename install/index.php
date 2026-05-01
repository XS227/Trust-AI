<?php
// /install/index.php
if (file_exists(__DIR__ . '/../inc/config.local.php')) {
  header("Location: /");
  exit;
}
?>
<!doctype html>
<html lang="no">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TrustAI – Install Wizard</title>
  <style>
    body{margin:0;background:#0b1220;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
    .overlay{min-height:100vh;display:grid;place-items:center;padding:18px}
    .modal{width:min(920px,96vw);background:#fff;border-radius:18px;overflow:hidden}
    .head{padding:16px 18px;border-bottom:1px solid #e6e9f2;display:flex;justify-content:space-between;align-items:center}
    .body{padding:18px;display:grid;gap:14px}
    .steps{display:flex;gap:8px;flex-wrap:wrap}
    .pill{padding:8px 10px;border:1px solid #e6e9f2;border-radius:999px;font-weight:800;color:#5f6b7a}
    .pill.active{background:#f1f4ff;color:#1f3b82;border-color:#c7d2fe}
    .grid{display:grid;grid-template-columns:1fr;gap:12px}
    @media(min-width:900px){.grid{grid-template-columns:1fr 1fr}}
    label{font-size:13px;color:#5f6b7a;font-weight:800}
    input,select{width:100%;border:1px solid #e6e9f2;border-radius:12px;padding:12px;font-size:14px}
    .row{display:grid;gap:8px}
    .actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;padding-top:8px}
    button{border:0;border-radius:12px;padding:12px 14px;font-weight:1000;cursor:pointer}
    .btn{background:#1f3b82;color:#fff}
    .btn2{background:#fff;border:1px solid #e6e9f2;color:#0b1220}
    .note{border:1px solid #e6e9f2;border-radius:12px;padding:12px;background:#fbfcff;color:#5f6b7a;line-height:1.5}
    .ok{color:#0f766e;font-weight:900}
    .bad{color:#b91c1c;font-weight:900}
    .small{font-size:12px;color:#5f6b7a}
    code{background:#f6f7fb;padding:2px 6px;border-radius:8px}
  </style>
</head>
<body>
  <div class="overlay">
    <div class="modal">
      <div class="head">
        <div>
          <div style="font-weight:1100;font-size:16px">TrustAI – Install Wizard</div>
          <div class="small">Første gangs oppsett</div>
        </div>
        <div class="small">Domene: <code>trustai.no</code></div>
      </div>

      <div class="body">
        <div class="steps">
          <div class="pill active" data-step-pill="1">1) Database</div>
          <div class="pill" data-step-pill="2">2) Opprett tabeller</div>
          <div class="pill" data-step-pill="3">3) Vipps</div>
          <div class="pill" data-step-pill="4">4) Ferdig</div>
        </div>

        <!-- STEP 1 -->
        <section data-step="1">
          <div class="note">
            Legg inn MySQL-detaljene fra ProISP. Trykk <b>Test tilkobling</b>.
          </div>

          <div class="grid">
            <div class="row">
              <label>DB Host</label>
              <input id="db_host" placeholder="mysql.xxxxx.service.one" />
            </div>
            <div class="row">
              <label>DB Name</label>
              <input id="db_name" placeholder="cifms3sug_db..." />
            </div>
            <div class="row">
              <label>DB User</label>
              <input id="db_user" placeholder="cifms3sug_db..." />
            </div>
            <div class="row">
              <label>DB Password</label>
              <input id="db_pass" type="password" placeholder="********" />
            </div>
          </div>

          <div id="db_status" class="small"></div>

          <div class="actions">
            <button class="btn" id="btnTestDb">Test tilkobling</button>
            <button class="btn" id="btnNext1" disabled>Neste</button>
          </div>
        </section>

        <!-- STEP 2 -->
        <section data-step="2" style="display:none">
          <div class="note">
            Nå oppretter vi tabeller og en enkel admin-konto for systemet.
          </div>

          <div class="grid">
            <div class="row">
              <label>Admin e-post</label>
              <input id="admin_email" type="email" placeholder="admin@trustai.no" />
            </div>
            <div class="row">
              <label>Admin passord</label>
              <input id="admin_pass" type="password" placeholder="Sterkt passord" />
            </div>
          </div>

          <div id="schema_status" class="small"></div>

          <div class="actions">
            <button class="btn2" id="btnBack2">Tilbake</button>
            <button class="btn" id="btnCreateSchema">Opprett tabeller</button>
            <button class="btn" id="btnNext2" disabled>Neste</button>
          </div>
        </section>

        <!-- STEP 3 -->
        <section data-step="3" style="display:none">
          <div class="note">
            Legg inn Vipps-innstillingene. Du kan starte med <b>test</b>. Ikke del secrets offentlig.
          </div>

          <div class="grid">
            <div class="row">
              <label>Miljø</label>
              <select id="vipps_env">
                <option value="test">Test</option>
                <option value="prod">Produksjon</option>
              </select>
            </div>
            <div class="row">
              <label>Merchant Serial Number (MSN)</label>
              <input id="vipps_msn" value="437233" />
            </div>
            <div class="row">
              <label>Client ID</label>
              <input id="vipps_client_id" placeholder="..." />
            </div>
            <div class="row">
              <label>Client Secret</label>
              <input id="vipps_client_secret" type="password" placeholder="..." />
            </div>
            <div class="row">
              <label>Subscription Key (primary)</label>
              <input id="vipps_sub_primary" type="password" placeholder="..." />
            </div>
          </div>

          <div class="note small">
            Redirect URI du må ha registrert i Vipps-portalen:
            <code>https://trustai.no/auth/vipps/callback.php</code>
          </div>

          <div id="vipps_status" class="small"></div>

          <div class="actions">
            <button class="btn2" id="btnBack3">Tilbake</button>
            <button class="btn" id="btnSaveVipps">Lagre Vipps</button>
            <button class="btn" id="btnNext3" disabled>Neste</button>
          </div>
        </section>

        <!-- STEP 4 -->
        <section data-step="4" style="display:none">
          <div class="note">
            Ferdig. Systemet er installert. Av sikkerhet bør du nå slette eller låse <code>/install</code>.
          </div>

          <div id="finish_status" class="note"></div>

          <div class="actions">
            <button class="btn" id="btnFinish">Gå til siden</button>
          </div>
        </section>

      </div>
    </div>
  </div>

  <script src="/install/installer.js"></script>
</body>
</html>

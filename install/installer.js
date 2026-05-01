const $ = (s) => document.querySelector(s);
const $$ = (s) => Array.from(document.querySelectorAll(s));

function showStep(n){
  $$('[data-step]').forEach(sec => sec.style.display = (sec.getAttribute('data-step') === String(n)) ? 'block' : 'none');
  $$('[data-step-pill]').forEach(p => p.classList.toggle('active', p.getAttribute('data-step-pill') === String(n)));
}

async function post(action, payload){
  const res = await fetch('/install/api.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action, payload})
  });
  return await res.json();
}

let cachedDb = null;

$('#btnTestDb').addEventListener('click', async () => {
  $('#db_status').textContent = 'Tester...';
  const payload = {
    host: $('#db_host').value.trim(),
    name: $('#db_name').value.trim(),
    user: $('#db_user').value.trim(),
    pass: $('#db_pass').value
  };
  const out = await post('test_db', payload);
  if(out.ok){
    cachedDb = payload;
    $('#db_status').innerHTML = '<span class="ok">OK:</span> Tilkobling fungerer.';
    $('#btnNext1').disabled = false;
  }else{
    $('#db_status').innerHTML = `<span class="bad">Feil:</span> ${out.error || 'Ukjent feil'}`;
    $('#btnNext1').disabled = true;
  }
});

$('#btnNext1').addEventListener('click', () => showStep(2));
$('#btnBack2').addEventListener('click', () => showStep(1));

$('#btnCreateSchema').addEventListener('click', async () => {
  $('#schema_status').textContent = 'Oppretter tabeller...';
  const payload = {
    db: cachedDb,
    admin_email: $('#admin_email').value.trim(),
    admin_pass: $('#admin_pass').value
  };
  const out = await post('create_schema', payload);
  if(out.ok){
    $('#schema_status').innerHTML = '<span class="ok">OK:</span> Tabeller opprettet.';
    $('#btnNext2').disabled = false;
  }else{
    $('#schema_status').innerHTML = `<span class="bad">Feil:</span> ${out.error || 'Ukjent feil'}`;
    $('#btnNext2').disabled = true;
  }
});

$('#btnNext2').addEventListener('click', () => showStep(3));
$('#btnBack3').addEventListener('click', () => showStep(2));

$('#btnSaveVipps').addEventListener('click', async () => {
  $('#vipps_status').textContent = 'Lagrer Vipps...';
  const payload = {
    db: cachedDb,
    vipps: {
      env: $('#vipps_env').value,
      merchantSerialNumber: $('#vipps_msn').value.trim(),
      client_id: $('#vipps_client_id').value.trim(),
      client_secret: $('#vipps_client_secret').value,
      subscription_key_primary: $('#vipps_sub_primary').value
    }
  };
  const out = await post('save_vipps_and_finalize', payload);
  if(out.ok){
    $('#vipps_status').innerHTML = '<span class="ok">OK:</span> Lagret.';
    $('#btnNext3').disabled = false;
  }else{
    $('#vipps_status').innerHTML = `<span class="bad">Feil:</span> ${out.error || 'Ukjent feil'}`;
    $('#btnNext3').disabled = true;
  }
});

$('#btnNext3').addEventListener('click', async () => {
  showStep(4);
  $('#finish_status').innerHTML = `
    <b>Installert!</b><br>
    Konfigurasjon er skrevet til <code>/inc/config.local.php</code>.<br>
    Anbefaling: gi <code>/install</code> passord, eller slett mappen etterpå.
  `;
});

$('#btnFinish').addEventListener('click', () => {
  window.location.href = '/';
});

// start
showStep(1);

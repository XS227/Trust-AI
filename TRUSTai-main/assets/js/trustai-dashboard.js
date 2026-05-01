(function(){
  const ui = window.DashboardUI;
  function toast(msg){const t=document.createElement('div');t.className='toast';t.textContent=msg;document.body.appendChild(t);setTimeout(()=>t.remove(),2200)}
  const qs=(id)=>document.getElementById(id);
  const post=(url,payload)=>fetch(url,{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload||{})}).then(r=>r.json());
  function closeModal(){const b=qs('globalModal');if(b) b.style.display='none';}
  function showModal(title,bodyHtml,onSubmit,submitLabel='Lagre'){
    const back=qs('globalModal'); const content=qs('globalModalContent'); if(!back||!content) return;
    content.innerHTML=`<div class="modal-header"><h3>${ui.escapeHtml(title)}</h3><button class="btn secondary" id="closeModal">✕</button></div>${bodyHtml}<div class='modal-actions'><button class='btn secondary' id='cancelModal'>Avbryt</button><button class='btn' id='submitModal'>${ui.escapeHtml(submitLabel)}</button></div>`;
    back.style.display='flex';
    const close=()=>closeModal();
    qs('closeModal').onclick=close; qs('cancelModal').onclick=close;
    back.onclick=(e)=>{if(e.target===back) close();};
    document.onkeydown=(e)=>{if(e.key==='Escape') close();};
    qs('submitModal').onclick=async()=>{const btn=qs('submitModal');btn.disabled=true;btn.textContent='Lagrer...';const ok=await onSubmit();btn.disabled=false;btn.textContent=submitLabel;if(ok!==false) close();};
  }
  function setupSidebar(){
    const app=document.querySelector('.app'); if(!app) return;
    if(!document.querySelector('.sidebar-overlay')){const ov=document.createElement('div');ov.className='sidebar-overlay';ov.onclick=()=>app.classList.remove('sidebar-open');document.body.appendChild(ov);}    
    const s=document.querySelector('.sidebar');
    if(s && !s.querySelector('.sidebar-close')){const b=document.createElement('button');b.className='btn secondary sidebar-close';b.textContent='✕';b.onclick=()=>app.classList.remove('sidebar-open');s.prepend(b);}
    s?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>app.classList.remove('sidebar-open')));
  }
  function toggleSidebar(){document.querySelector('.app')?.classList.toggle('sidebar-open')}
  function normalizeDomain(v){return (v||'').toLowerCase().replace(/^https?:\/\//,'').replace(/\/$/,'').trim();}
  window.TrustAIDashboard={toast,qs,post,showModal,closeModal,toggleSidebar,setupSidebar,normalizeDomain};
  document.addEventListener('DOMContentLoaded',setupSidebar);
})();

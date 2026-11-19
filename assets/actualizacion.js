/* Actualización de datos compartidos. Los formularios se conservan en el DOM. */
(() => {
 'use strict';
 let version = null, busy = false, stopped = false, timer;
 const notice = document.createElement('p');
 notice.className = 'form-hint'; notice.setAttribute('role', 'status');
 notice.setAttribute('aria-live', 'polite'); notice.textContent = 'Actualización automática · cada 3 s';
 document.querySelector('.page-head')?.after(notice);
 const say = text => { if (notice.textContent !== text) notice.textContent = text; };
 const fetching = async url => {
  const abort = new AbortController(), timeout = setTimeout(() => abort.abort(), 10000);
  try { return await fetch(url, {cache:'no-store', credentials:'same-origin', headers:{'X-Parking-Refresh':'1'}, signal:abort.signal}); }
  finally { clearTimeout(timeout); }
 };
 const snapshotAddress = () => {
  const url = new URL(location.href), field = name => document.querySelector(`main form[method="post"] input[type="hidden"][name="${name}"]`)?.value;
  // Tras un POST rechazado, la URL puede perder el ID aunque siga habiendo un borrador de edición.
  if(url.pathname.endsWith('/salida.php') && field('ingreso'))url.searchParams.set('ingreso',field('ingreso'));
  else if(url.pathname.endsWith('/ingresos.php') && field('vehiculo'))url.searchParams.set('vehiculo',field('vehiculo'));
  else if(Number(field('id'))>0){
   if(url.pathname.endsWith('/usuarios.php'))url.searchParams.set('editar',field('id'));
   else {url.searchParams.set('id',field('id'));url.searchParams.set('accion',field('action')==='eliminar'?'eliminar':'editar');}
  }
  return url;
 };
 const patch = fresh => {
  // No sustituir elementos con los que la persona está interactuando.
  if (document.activeElement?.matches('select') || document.querySelector('dialog[open]') || !window.getSelection().isCollapsed) return false;
  const y = window.scrollY;
  const oldBodies = [...document.querySelectorAll('main table tbody')];
  const newBodies = [...fresh.querySelectorAll('main table tbody')];
  if (oldBodies.length !== newBodies.length) throw new Error('Cambió la estructura de la ficha');
  oldBodies.forEach((body, i) => {
   const open = [...body.querySelectorAll('tr')].filter(r => r.querySelector('details[open]')).map(r => r.textContent);
   body.replaceChildren(...[...newBodies[i].childNodes].map(n => n.cloneNode(true)));
   body.querySelectorAll('tr').forEach(r => {if (open.includes(r.textContent)) r.querySelectorAll('details').forEach(d => d.open = true);});
  });
  let missing = false;
  document.querySelectorAll('main select').forEach(select => {
   const next = select.id ? fresh.getElementById(select.id) :
    [...fresh.querySelectorAll('main select')].find(s => s.name === select.name && s.form?.getAttribute('action') === select.form?.getAttribute('action'));
   if (!next) return;
   const old = select.value;
   select.replaceChildren(...[...next.options].map(o => o.cloneNode(true)));
   if ([...select.options].some(o => o.value === old)) select.value = old;
   else {select.selectedIndex = -1; if (old) missing = true;}
   select.disabled = next.disabled;
  });
  document.dispatchEvent(new Event('parking:catalogos'));
  ['.table-toolbar .count', '#row-count'].forEach(selector => {
   const old = [...document.querySelectorAll(selector)], next = [...fresh.querySelectorAll(selector)];
   old.forEach((el,i) => {if(next[i])el.textContent = next[i].textContent;});
  });
  const selector = 'section[aria-label="Responsable del registro"], section[aria-label="Resumen de espacios"], .crud-detail, section[aria-label="Resumen real"]';
  const details = [...document.querySelectorAll(selector)], nextDetails = [...fresh.querySelectorAll(selector)];
  details.forEach((el,i) => {
   const next = nextDetails[i];
   if(next && !el.querySelector('form,input,select,textarea') && !next.querySelector('form,input,select,textarea'))el.replaceChildren(...[...next.childNodes].map(n=>n.cloneNode(true)));
  });
  const formKey = form => JSON.stringify([form.getAttribute('action'), ...['action','id','ingreso','vehiculo'].map(name=>form.querySelector(`input[type="hidden"][name="${name}"]`)?.value || '')]);
  document.querySelectorAll('main form[method="post"]').forEach(form => {
   const next = [...fresh.querySelectorAll('main form[method="post"]')].find(f=>formKey(f)===formKey(form));
   if(!next){form.querySelectorAll('button').forEach(b=>b.disabled=true);form.querySelectorAll('select').forEach(s=>{s.selectedIndex=-1;s.disabled=true;});missing=true;}
   else {const buttons=[...next.querySelectorAll('button[type="submit"],button:not([type])')];form.querySelectorAll('button[type="submit"],button:not([type])').forEach((b,i)=>{if(buttons[i])b.disabled=buttons[i].disabled;});}
  });
  // Reaplicar los filtros existentes sin enviar formularios ni cambiar sus valores.
  document.querySelectorAll('main input[type="search"]').forEach(input => input.dispatchEvent(new Event('input', {bubbles:true})));
  document.getElementById('movement-filter')?.dispatchEvent(new Event('change'));
  window.scrollTo(window.scrollX,y);
  say(missing ? 'Datos actualizados. Una opción dejó de estar disponible: revisa tu selección.' : 'Actualizado · comprobación cada 3 s');
  return true;
 };
 const check = async (force = false) => {
  if(busy || stopped || document.hidden) return;
  busy = true;
  try {
   const res = await fetching(new URL('cambios.php', location.href));
   if(res.status === 401 || res.status === 403){stopped=true;clearInterval(timer);say('Tu sesión o tus permisos cambiaron. Inicia sesión nuevamente; tu formulario se conserva.');return;}
   if(!res.ok)throw new Error('Sin conexión');
   const data = await res.json();
   if(version !== data.version || force){
    const response = await fetching(snapshotAddress());
    if(response.status===401||response.status===403||response.redirected){stopped=true;clearInterval(timer);say('Tu sesión o tus permisos cambiaron. Inicia sesión nuevamente; tu formulario se conserva.');return;}
    if(!response.ok || response.redirected)throw new Error('No se pudo actualizar la ficha');
    const fresh = new DOMParser().parseFromString(await response.text(),'text/html');
    if(!fresh.querySelector('main'))throw new Error('Respuesta inesperada');
    if(patch(fresh))version=data.version;
   }else if(notice.textContent.startsWith('Sin conexión'))say('Actualizado · comprobación cada 3 s');
  }catch(error){say('Sin conexión para actualizar. Tu formulario se conserva; se reintentará automáticamente.');}
  finally{busy=false;}
 };
 document.addEventListener('submit',event=>{if(event.target.method?.toLowerCase()==='post'){stopped=true;clearInterval(timer);}});
 window.addEventListener('focus',()=>check(true));
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)check(true);});
 timer=setInterval(()=>check(),3000);check();
})();

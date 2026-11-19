'use strict';
// Prototipo de interfaces. No fetch, base de datos, localStorage ni contraseñas persistidas.
const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const toast = message => {
  const box = $('.toast'); if (!box) return;
  box.textContent = message; box.hidden = false;
  clearTimeout(toast.timer); toast.timer = setTimeout(() => {box.hidden = true;}, 6500);
};
const icon = name => {
  const paths = {edit:'<path d="m16 3 5 5-12 12-6 1 1-6zM13 6l5 5"/>',trash:'<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>',eye:'<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',state:'<path d="M4 7h16M4 17h16"/><circle cx="8" cy="7" r="3"/><circle cx="16" cy="17" r="3"/>'};
  return `<svg viewBox="0 0 24 24" aria-hidden="true">${paths[name] || paths.eye}</svg>`;
};
const menu = $('.menu-toggle'), sidebar = $('.sidebar'), overlay = $('.mobile-overlay');
function closeMenu() {sidebar?.classList.remove('open');overlay?.classList.remove('open');menu?.setAttribute('aria-expanded','false');}
menu?.addEventListener('click', () => {const opened = sidebar.classList.toggle('open');overlay.classList.toggle('open', opened);menu.setAttribute('aria-expanded', String(opened));});
overlay?.addEventListener('click', closeMenu);
document.addEventListener('keydown', event => {if (event.key === 'Escape') closeMenu();});
let confirmAction = null, dialogForm = null;
function modal(title, text, action, label = 'Confirmar') {
  const dialog = $('#dialog'); if (!dialog) return;
  $('#dialog-title').textContent = title; $('#dialog-text').textContent = text;
  $('#dialog-fields').replaceChildren(); dialogForm = null;
  $('#dialog-confirm').textContent = label; confirmAction = action;
  $('#dialog-cancel').textContent = action ? 'Cancelar' : 'Cerrar';
  $('#dialog-confirm').hidden = !action; dialog.showModal();
}
$('#dialog-cancel')?.addEventListener('click', () => $('#dialog').close());
$('#dialog')?.addEventListener('close', () => {$('#dialog-fields').replaceChildren();dialogForm = null;confirmAction = null;});
$('#dialog-confirm')?.addEventListener('click', () => {
  if (dialogForm && !dialogForm.reportValidity()) return;
  const action = confirmAction;
  if (action && action() === false) return;
  $('#dialog').close();
});
$('[data-password]')?.addEventListener('click', () => {
  modal('Cambiar mi contraseña', 'Prueba visual: no uses tu contraseña real. No se guardará ningún dato.', () => {
    const fields = [...dialogForm.elements];
    if (fields[1].value !== fields[2].value) {fields[2].setCustomValidity('Las contraseñas no coinciden.');fields[2].reportValidity();return false;}
    toast('Formulario validado. El cambio de contraseña real se conectará con PHP.');
  }, 'Validar ejemplo');
  dialogForm = document.createElement('form');
  ['Contraseña actual','Nueva contraseña (12 caracteres mínimo)','Repite la nueva contraseña'].forEach((text,index) => {
    const label = document.createElement('label'), input = document.createElement('input');
    label.textContent = text;input.type = 'password';input.required = true;input.minLength = index ? 12 : 1;input.maxLength = 128;
    input.autocomplete = index ? 'new-password' : 'current-password';input.addEventListener('input', () => input.setCustomValidity(''));
    label.append(input);dialogForm.append(label);
  });
  dialogForm.addEventListener('submit', e => {e.preventDefault();$('#dialog-confirm').click();});$('#dialog-fields').append(dialogForm);
});
const auth = $('#auth-form');
auth?.addEventListener('submit', event => {
  event.preventDefault();
  if (auth.elements.confirmar && auth.elements.clave.value !== auth.elements.confirmar.value) {
    auth.elements.confirmar.setCustomValidity('Las contraseñas no coinciden.');auth.elements.confirmar.reportValidity();return;
  }
  auth.reset();location.href = document.body.dataset.page === 'configuracion' ? 'login.php' : 'inicio.php';
});
auth?.addEventListener('input', () => auth.elements.confirmar?.setCustomValidity(''));
const data = $('#page-data');
if (data) {
  const page = JSON.parse(data.textContent), form = $('#record-form');
  let rows = page.rows.map(row => ({...row})), editing = -1, visitFilter = null;
  const currency = value => new Intl.NumberFormat('es-CO', {style:'currency',currency:'COP',maximumFractionDigits:0}).format(Number(value));
  const stamp = () => new Intl.DateTimeFormat('es-CO',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'}).format(new Date());
  const cellText = (row,key) => ['base','adicional'].includes(key) ? currency(row[key]) : String(row[key] ?? '—');
  function render() {
    const body = $('#records');body.replaceChildren();
    const term = $('#search').value.trim().toLocaleLowerCase('es');
    let visible = 0;
    rows.forEach((row,index) => {
      if (visitFilter && row.movimiento !== visitFilter) return;
      if (!page.columns.some(column => cellText(row,column.key).toLocaleLowerCase('es').includes(term))) return;
      visible++;
      const tr = document.createElement('tr');
      page.columns.forEach(column => {
        const td = document.createElement('td'), value = cellText(row,column.key);
        if (column.key === 'placa') {const span=document.createElement('span');span.className='plate';span.textContent=value;td.append(span);}
        else if (['estado','salida'].includes(column.key) && ['Libre','Activo','VIGENTE','Habilitado','Ocupado','Mantenimiento','Inactivo','CADUCADA','Sin acceso','En parqueadero'].includes(value)) {
          const span=document.createElement('span');span.className='tag'+(['Mantenimiento','Sin acceso'].includes(value)?' warn':['Inactivo','CADUCADA'].includes(value)?' gray':['Ocupado','En parqueadero'].includes(value)?' blue':'');span.textContent=value;td.append(span);
        } else {td.textContent=value.length > 65 ? value.slice(0,65)+'…' : value;td.title=value;}
        tr.append(td);
      });
      const actions=document.createElement('td'), group=document.createElement('div');group.className='row-actions';
      const button=(name,label,callback) => {const b=document.createElement('button');b.type='button';b.title=label;b.setAttribute('aria-label',label);b.innerHTML=icon(name);b.addEventListener('click',callback);group.append(b);};
      if (page.key === 'ingresos' && row.salida === 'En parqueadero') {
        const a=document.createElement('a');a.href='ingresos.php?placa='+encodeURIComponent(row.placa);a.className='btn small';a.textContent='Registrar salida';group.append(a);
      }
      button('eye','Ver detalle de '+(row.nombre||row.placa||row.tipo||row.numero), () => modal('Detalle del registro',page.columns.map(c=>c.label+': '+cellText(row,c.key)).join('\n'),null));
      if(page.edit) button('edit','Editar '+(row.nombre||row.placa||row.numero),()=>{
        if(page.key==='espacios' && row.estado!=='Libre') {toast('Solo se editan espacios libres. Habilita primero los que están en mantenimiento.');return;}
        editing=index;
        page.fields.forEach(field=>{const input=form.elements[field.key];input.value=field.type==='password'?'':row[field.key]??'';if(field.type==='password') input.required=false;});
        $('#save-button').textContent='Guardar cambios';form.scrollIntoView({behavior:'smooth',block:'center'});form.querySelector('input,select').focus({preventScroll:true});
      });
      if(page.delete) button('trash','Eliminar '+(row.nombre||row.placa),()=>modal('Eliminar registro de ejemplo','¿Quieres quitar este registro de la vista previa? No se eliminará ningún dato de MySQL.',()=>{rows.splice(index,1);form.reset();render();toast('Registro quitado de esta vista previa.');},'Eliminar ejemplo'));
      if(page.state) button('state','Cambiar estado de '+(row.nombre||row.numero),()=>{
        if(page.key==='espacios' && row.estado==='Ocupado') {toast('Un espacio ocupado se libera al registrar la salida.');return;}
        if(page.key==='usuarios' && row.rol==='Administrador' && rows.filter(r=>r.rol==='Administrador').length===1) {toast('Debe permanecer al menos un administrador habilitado.');return;}
        const next=page.key==='espacios'?(row.estado==='Libre'?'Mantenimiento':'Libre'):page.key==='usuarios'?(row.estado==='Sin acceso'?'Habilitado':'Sin acceso'):(row.estado==='Activo'?'Inactivo':'Activo');
        modal('Cambiar disponibilidad','El nuevo estado del registro de ejemplo será: '+next+'.',()=>{row.estado=next;if(page.key==='usuarios') row.rol=next==='Sin acceso'?'Sin acceso':'Operador';render();toast('Estado actualizado solo en esta vista previa.');});
      });
      actions.append(group);tr.append(actions);body.append(tr);
    });
    $('#row-count').textContent=rows.length;$('#results').textContent=`Mostrando ${visible} de ${rows.length} registros de ejemplo`;
    $('#empty').hidden=visible!==0;
    if(page.key==='espacios') {
      const grid=$('.space-grid');grid.replaceChildren();
      rows.forEach(row=>{const tile=document.createElement('div');tile.className='space-tile'+(row.estado==='Ocupado'?' busy':row.estado==='Mantenimiento'?' maintenance':'');const num=document.createElement('strong'),label=document.createElement('small');num.textContent=row.numero;label.textContent=row.tipo+' · '+row.estado;tile.append(num,label);grid.append(tile);});
    }
  }
  $('#search').addEventListener('input',render);
  form.addEventListener('input',event=>event.target.setCustomValidity?.(''));
  if(page.key==='incidentes') {
    const filter=document.createElement('button');filter.className='btn small';filter.type='button';filter.textContent='Ver de la visita elegida';
    filter.addEventListener('click',()=>{if(!visitFilter && !form.elements.movimiento.value){toast('Selecciona primero una visita en el formulario.');return;}visitFilter=visitFilter?null:form.elements.movimiento.value;filter.textContent=visitFilter?'Ver todas las visitas':'Ver de la visita elegida';render();});$('.table-toolbar').append(filter);
  }
  if(page.key==='ingresos') {
    const spaces={'Carro':['Espacio 01 · Carro','Espacio 02 · Carro'],'Moto':['Espacio 11 · Moto','Espacio 13 · Moto'],'Camioneta':['Espacio 18 · Camioneta'],'Bicicleta':['Espacio 19 · Bicicleta','Espacio 20 · Bicicleta']};
    form.elements.espacio.disabled=true;
    form.elements.placa.addEventListener('change',()=>{const options=spaces[form.elements.placa.value.split(' — ')[1]]||[];form.elements.espacio.replaceChildren(new Option('Selecciona un espacio',''));options.forEach(label=>form.elements.espacio.add(new Option(label,label)));form.elements.espacio.disabled=!options.length;});
  }
  if(page.key==='tarifas') {form.elements.anio.value='2026';form.elements.anio.min=2020;form.elements.anio.max=2050;form.elements.horas.value='2';form.elements.horas.min=1;form.elements.horas.max=24;for(const key of ['base','adicional']) form.elements[key].step='.01';}
  if(page.key==='espacios') form.elements.numero.min=1;
  form.addEventListener('reset',()=>{editing=-1;$('#save-button').textContent=page.button;for(const field of page.fields){const control=form.elements[field.key];control.setCustomValidity('');if(field.type==='password')control.required=true;}if(page.key==='ingresos'){form.elements.espacio.replaceChildren(new Option('Selecciona una placa primero',''));form.elements.espacio.disabled=true;}});
  form.addEventListener('submit',event=>{
    event.preventDefault();const row=Object.fromEntries(new FormData(form));
    for(const field of page.fields) if(field.type!=='password') row[field.key]=String(row[field.key]??'').trim();
    const requiredBlank=page.fields.find(f=>!f.optional && f.type!=='password' && !row[f.key]);
    if(requiredBlank){form.elements[requiredBlank.key].setCustomValidity('Completa este campo.');form.elements[requiredBlank.key].reportValidity();return;}
    if(page.key==='usuarios') {
      if(row.clave!==row.confirmar){form.elements.confirmar.setCustomValidity('Las contraseñas no coinciden.');form.elements.confirmar.reportValidity();return;}
      if(editing>=0 && rows[editing].rol==='Administrador' && row.rol!=='Administrador' && rows.filter(r=>r.rol==='Administrador').length===1){toast('Debe permanecer un administrador con acceso.');return;}
      row.estado=row.rol==='Sin acceso'?'Sin acceso':'Habilitado';delete row.clave;delete row.confirmar;
    }
    const unique={clientes:'documento',vehiculos:'placa',usuarios:'usuario',pagos:'nombre',espacios:'numero'}[page.key];
    if(unique && rows.some((r,i)=>i!==editing && (page.key==='espacios'?Number(r[unique])===Number(row[unique]):String(r[unique]).localeCompare(row[unique],'es',{sensitivity:'base'})===0))){form.elements[unique].setCustomValidity('Ya existe un registro con este valor.');form.elements[unique].reportValidity();return;}
    if(page.key==='ingresos'){row.placa=row.placa.split(' — ')[0];if(rows.some(r=>r.salida==='En parqueadero'&&(r.placa===row.placa||r.espacio===row.espacio))){toast('Ese vehículo o espacio ya tiene una entrada abierta en esta demostración.');return;}row.entrada=stamp();row.salida='En parqueadero';row.total='—';row.metodo='—';}
    if(page.key==='vehiculos') row.placa=row.placa.toUpperCase();
    if(page.key==='espacios')row.estado=editing>=0?rows[editing].estado:'Libre';
    if(page.key==='tarifas')row.codigo='TAR-DEMO-'+String(Date.now()).slice(-8);
    if(page.key==='incidentes')row.fecha=stamp();
    if(editing>=0)rows[editing]={...rows[editing],...row};else rows.unshift(row);
    form.reset();render();toast('Vista previa actualizada. Los cambios no se guardan y se reinician al salir de esta pantalla.');
  });
  render();
}
const exitForm=$('#exit-form');
if(exitForm){
  const requested=new URLSearchParams(location.search).get('placa')||'ABC123';
  const known={ABC123:{name:'Valentina Ríos',space:'03 · Carro',type:'Carro',hour:5000,extra:1000,day:22000,month:180000},XYZ45F:{name:'Mateo Torres',space:'12 · Moto',type:'Moto',hour:2500,extra:500,day:12000,month:90000},JKL789:{name:'Camila Gómez',space:'18 · Camioneta',type:'Camioneta',hour:6000,extra:1500,day:28000,month:220000},BICI01:{name:'Andrés López',space:'19 · Bicicleta',type:'Bicicleta',hour:1000,extra:500,day:5000,month:40000}};
  const vehicle=known[requested]||known.ABC123;$('#exit-plate').textContent=known[requested]?requested:'ABC123';$('#exit-client').textContent=vehicle.name;$('#exit-space').textContent=vehicle.space;
  const money=n=>new Intl.NumberFormat('es-CO',{style:'currency',currency:'COP',maximumFractionDigits:0}).format(n);
  [['hora',vehicle.hour,'Hora'],['dia',vehicle.day,'Día'],['mes',vehicle.month,'Mes']].forEach(([value,amount,label],i)=>{$('#exit-rate').options[i].textContent=label+' · '+vehicle.type+' · '+money(amount);});
  const update=()=>{const mode=$('#exit-rate').value,base=mode==='hora'?vehicle.hour:mode==='dia'?vehicle.day:vehicle.month,extra=mode==='hora'?vehicle.extra:0;$('#base-value').textContent=money(base);$('#extra-value').textContent=money(extra);$('#exit-total').textContent=money(base+extra);};
  $('#exit-rate').addEventListener('change',update);update();
  exitForm.addEventListener('submit',event=>{event.preventDefault();modal('Confirmar salida de ejemplo',`${$('#exit-plate').textContent} · ${$('#exit-total').textContent}\nMétodo: ${$('#exit-method').value}\nEsta acción solo demuestra la confirmación. No registra un pago real.`,()=>{toast('Simulación completada. No se modificó ningún movimiento ni espacio en MySQL.');},'Confirmar demostración');});
}

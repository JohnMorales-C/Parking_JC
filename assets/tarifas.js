'use strict';
function filterRates(){
 const term=document.querySelector('#rate-search')?.value.trim().toLocaleLowerCase('es')||'';
 const state=document.querySelector('#rate-filter')?.value||'';let count=0;
 document.querySelectorAll('#rate-rows tr').forEach(row=>{
  row.hidden=!!((state&&row.dataset.status!==state)||![...row.cells].slice(0,-1).some(c=>c.textContent.toLocaleLowerCase('es').includes(term)));
  if(!row.hidden)count++;
 });
 const counter=document.querySelector('#row-count');if(counter)counter.textContent=count;
 const empty=document.querySelector('#rate-empty');if(empty)empty.hidden=count!==0;
}
document.querySelector('#rate-search')?.addEventListener('input',filterRates);
document.querySelector('#rate-filter')?.addEventListener('change',filterRates);

// HORA conserva un precio uniforme; DIA/MES permiten adicional tras el periodo incluido.
(() => {
 const modalidad=document.getElementById('rate-tipo_cobro');if(!modalidad)return;
 const actualizar=()=>{
  const extra=modalidad.value==='DIA'||modalidad.value==='MES';
  document.querySelectorAll('.rate-period-extra').forEach(label=>{
   label.hidden=!extra;label.querySelectorAll('input').forEach(input=>{input.disabled=!extra;input.required=extra;});
  });
  const label=document.getElementById('rate-price-label');
  if(label)label.textContent=modalidad.value==='DIA'?'Precio por día, 24 horas ($)':modalidad.value==='MES'?'Precio por mes, 30 días ($)':'Precio por hora ($)';
 };
 modalidad.addEventListener('change',actualizar);document.addEventListener('parking:catalogos',actualizar);actualizar();
})();
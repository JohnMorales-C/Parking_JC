'use strict';
function filterPayments(){
 const term=document.querySelector('#payment-search')?.value.trim().toLocaleLowerCase('es')||'';
 const state=document.querySelector('#payment-filter')?.value||'';let count=0;
 document.querySelectorAll('#payment-rows tr').forEach(row=>{
  row.hidden=!!((state&&row.dataset.status!==state)||![...row.cells].slice(0,3).some(c=>c.textContent.toLocaleLowerCase('es').includes(term)));
  if(!row.hidden)count++;
 });
 const counter=document.querySelector('#row-count');if(counter)counter.textContent=count;
 const empty=document.querySelector('#payment-empty');if(empty)empty.hidden=count!==0;
}
document.querySelector('#payment-search')?.addEventListener('input',filterPayments);
document.querySelector('#payment-filter')?.addEventListener('change',filterPayments);

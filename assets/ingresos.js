'use strict';
const choiceMovement=document.querySelector('#vehicle-choice-select');
choiceMovement?.addEventListener('change',()=>{if(choiceMovement.value) document.querySelector('#vehicle-choice').requestSubmit();});
function filterMovements(){
 const normalizar=value=>value.normalize('NFD').replace(/\p{M}/gu,'').toLocaleLowerCase('es');
 const term=normalizar(document.querySelector('#movement-search')?.value.trim()||'');
 const state=document.querySelector('#movement-filter')?.value||'';let count=0;
 document.querySelectorAll('#movement-rows tr').forEach(row=>{
  row.hidden=!!((state&&row.dataset.status!==state)||!([...row.cells].slice(0,-1).some(c=>normalizar(c.textContent).includes(term))||normalizar(row.dataset.search??'').includes(term)));
  if(!row.hidden)count++;
 });
 const counter=document.querySelector('#row-count');if(counter)counter.textContent=count;
 const empty=document.querySelector('#movement-empty');if(empty)empty.hidden=count!==0;
}
document.querySelector('#movement-search')?.addEventListener('input',filterMovements);
document.querySelector('#movement-filter')?.addEventListener('change',filterMovements);

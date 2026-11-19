'use strict';
function filterSpaces(){
 const term=document.querySelector('#space-search')?.value.trim().toLocaleLowerCase('es')||'';
 const state=document.querySelector('#space-filter')?.value||'';
 const type=document.querySelector('#space-type-filter')?.value||'';let count=0;
 document.querySelectorAll('#space-rows tr').forEach(row=>{
  row.hidden=!!((state&&row.dataset.status!==state)||(type&&row.dataset.type!==type)||![...row.cells].slice(0,4).some(c=>c.textContent.toLocaleLowerCase('es').includes(term)));
  if(!row.hidden)count++;
 });
 const counter=document.querySelector('#row-count');if(counter)counter.textContent=count;
 const empty=document.querySelector('#space-empty');if(empty)empty.hidden=count!==0;
}
for(const id of ['space-search','space-filter','space-type-filter'])document.getElementById(id)?.addEventListener(id==='space-search'?'input':'change',filterSpaces);

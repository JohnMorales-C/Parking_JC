'use strict';
document.querySelector('#incident-search')?.addEventListener('input',event=>{
 const normalizar=value=>value.normalize('NFD').replace(/\p{M}/gu,'').toLocaleLowerCase('es');
 const term=normalizar(event.target.value.trim());let count=0;
 document.querySelectorAll('#incident-rows tr').forEach(row=>{
  row.hidden=!([...row.cells].slice(0,4).some(cell=>normalizar(cell.textContent).includes(term))||normalizar(row.dataset.search??'').includes(term));if(!row.hidden)count++;
 });
 document.querySelector('#row-count').textContent=count;
 document.querySelector('#incident-empty').hidden=count!==0;
});

'use strict';
document.querySelector('#client-search')?.addEventListener('input', event => {
 const term = event.target.value.trim().toLocaleLowerCase('es');
 let count = 0;
 document.querySelectorAll('#client-rows tr').forEach(row => {
  row.hidden = ![...row.cells].slice(0,4).some(cell => cell.textContent.toLocaleLowerCase('es').includes(term));
  if (!row.hidden) count++;
 });
 document.querySelector('#row-count').textContent = count;
 document.querySelector('#client-empty').hidden = count !== 0;
});

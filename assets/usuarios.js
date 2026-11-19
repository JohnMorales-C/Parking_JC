'use strict';
document.querySelector('#user-search')?.addEventListener('input',e=>{
 let count=0;const term=e.target.value.trim().toLocaleLowerCase('es');
 document.querySelectorAll('#user-rows tr').forEach(r=>{r.hidden=![...r.cells].slice(0,4).some(c=>c.textContent.toLocaleLowerCase('es').includes(term));if(!r.hidden)count++;});
 document.querySelector('#user-empty').hidden=count!==0;
});

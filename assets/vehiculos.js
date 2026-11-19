'use strict';
['id_marca', 'id_color'].forEach(key => {
 const select = document.querySelector(`#campo-${key}`);
 const container = document.querySelector(`#extra-${key}`);
 const input = document.querySelector(key === 'id_marca' ? '#campo-otra_marca' : '#campo-otro_color');
 if (!select || !container || !input) return;
 const syncOther = () => {
  const label = select.selectedOptions[0]?.textContent.trim().toLocaleLowerCase('es');
  const other = label === 'otra' || label === 'otro';
  container.hidden = !other;
  input.required = other;
  input.disabled = !other;
  if (!other) input.value = '';
 };
 select.addEventListener('change', syncOther);
 syncOther();
});
// Se conserva el select nativo: solo se envía el ID del cliente seleccionado.
const clientSelect = document.querySelector('#campo-id_cliente');
const clientSearch = document.querySelector('#client-search');
if (clientSelect && clientSearch) {
 const tools = document.querySelector('#client-search-tools');
 const status = document.querySelector('#client-search-status');
 let clients = [...clientSelect.options].filter(option => option.value !== '')
  .map(option => ({ value: option.value, label: option.textContent }));
 const normalizeClient = value => value.normalize('NFD').replace(/\p{M}/gu, '').toLocaleLowerCase('es').trim();
 const filterClients = () => {
  const previous = clientSelect.value;
  const term = normalizeClient(clientSearch.value);
  const matches = clients.filter(client => normalizeClient(client.label).includes(term));
  clientSelect.replaceChildren(new Option('Selecciona una opción', ''));
  matches.forEach(client => clientSelect.add(new Option(client.label, client.value)));
  clientSelect.value = matches.some(client => client.value === previous) ? previous : '';
  status.textContent = matches.length
   ? `${matches.length} de ${clients.length} clientes. Selecciona el propietario.`
   : clients.length ? 'Sin coincidencias. Cambia la búsqueda.' : 'Registra primero un cliente.';
 };
 tools.hidden = false;
 document.addEventListener('parking:catalogos', () => {
  clients = [...clientSelect.options].filter(o => o.value !== '').map(o => ({value:o.value,label:o.textContent}));
  filterClients();
 });
 clientSearch.addEventListener('input', filterClients);
 filterClients();
}
document.querySelector('#vehicle-search')?.addEventListener('input', event => {
 const term = event.target.value.trim().toLocaleLowerCase('es');
 let count = 0;
 document.querySelectorAll('#vehicle-rows tr').forEach(row => {
  row.hidden = ![...row.cells].slice(0,6).some(cell => cell.textContent.toLocaleLowerCase('es').includes(term));
  if (!row.hidden) count++;
 });
 document.querySelector('#row-count').textContent = count;
 document.querySelector('#vehicle-empty').hidden = count !== 0;
});

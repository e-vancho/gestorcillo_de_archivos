const feedback = document.querySelector('#feedback');
document.querySelectorAll('.copy').forEach(button => button.addEventListener('click', async () => {
  try {
    await navigator.clipboard.writeText(button.dataset.url);
    feedback.textContent = 'Enlace copiado.';
  } catch {
    // Selección manual disponible incluso sin permiso del portapapeles.
    const input = document.createElement('input');
    input.value = button.dataset.url;
    input.readOnly = true;
    input.setAttribute('aria-label', 'Enlace público: selecciona y copia');
    feedback.replaceChildren(input);
    input.focus(); input.select();
  }
}));
document.querySelectorAll('.delete-form').forEach(form => form.addEventListener('submit', event => {
  if (!confirm(`¿Eliminar «${form.dataset.filename}»? Su enlace dejará de funcionar. Esta acción no se puede deshacer.`)) event.preventDefault();
}));
document.querySelector('#search')?.addEventListener('input', event => {
  const query = event.target.value.toLocaleLowerCase('es');
  let count = 0;
  document.querySelectorAll('.file-row').forEach(row => {
    row.hidden = !row.dataset.name.toLocaleLowerCase('es').includes(query);
    if (!row.hidden) count++;
  });
  document.querySelector('#empty').hidden = count > 0;
});
const file = document.querySelector('#file');
const selected = document.querySelector('#selected');
file?.addEventListener('change', () => { selected.textContent = file.files[0]?.name || 'Ningún archivo seleccionado'; });
const zone = document.querySelector('.upload');
zone?.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragging'); });
zone?.addEventListener('dragleave', () => zone.classList.remove('dragging'));
zone?.addEventListener('drop', e => {
  e.preventDefault(); zone.classList.remove('dragging');
  if (e.dataTransfer.files.length !== 1) { selected.textContent = 'Selecciona un solo archivo cada vez.'; return; }
  file.files = e.dataTransfer.files;
  file.dispatchEvent(new Event('change'));
});
document.querySelector('#upload-form')?.addEventListener('submit', () => {
  const button = document.querySelector('#upload-button');
  button.disabled = true; button.textContent = 'Subiendo…';
});

// Local image preview only. Files are uploaded by the Laravel form on Save.
document.querySelectorAll('[data-ag-board-form]').forEach((form) => {
  const picker = form.querySelector('[data-ag-board-file]');
  const preview = form.querySelector('[data-ag-board-preview]');
  const wrapper = form.querySelector('.ag-board-image-preview');
  if (!picker || !preview || !wrapper) return;

  let currentURL = null;
  picker.addEventListener('change', () => {
    if (currentURL) URL.revokeObjectURL(currentURL);
    currentURL = null;
    const file = picker.files && picker.files[0];
    if (!file || !file.type.startsWith('image/')) {
      preview.removeAttribute('src');
      wrapper.hidden = true;
      return;
    }
    currentURL = URL.createObjectURL(file);
    preview.src = currentURL;
    wrapper.hidden = false;
  });
});

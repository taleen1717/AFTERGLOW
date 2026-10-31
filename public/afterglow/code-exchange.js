/* AFTERGLOW Code Exchange: safe, user-initiated copy. No external libraries. */
(() => {
  const copyText = async (value) => {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(value);
      return;
    }
    const temporary = document.createElement('textarea');
    temporary.value = value;
    temporary.setAttribute('readonly', '');
    temporary.style.position = 'fixed';
    temporary.style.left = '-9999px';
    document.body.appendChild(temporary);
    temporary.select();
    const success = document.execCommand('copy');
    temporary.remove();
    if (!success) throw new Error('Clipboard not available');
  };

  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-ag-ce-copy]');
    if (!button) return;
    const code = button.closest('.ag-ce-code-wrap')?.querySelector('pre code');
    if (!code) return;
    const originalText = button.textContent;
    button.disabled = true;
    try {
      await copyText(code.textContent);
      button.textContent = '✓ Copied!';
    } catch (error) {
      button.textContent = 'Copy failed — select code';
    }
    window.setTimeout(() => {
      button.textContent = originalText;
      button.disabled = false;
    }, 1800);
  });
})();

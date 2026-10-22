// Amélioration progressive du site public : le bouton « Copier » n'apparaît que si le presse-papiers
// existe ; sinon le numéro reste un lien tel: classique. Aucun appel réseau.
document.querySelectorAll('[data-copy]').forEach((button) => {
  const target = document.querySelector(button.getAttribute('data-copy'));
  if (!target || !navigator.clipboard) return;
  const label = button.textContent;
  button.hidden = false;
  button.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(target.textContent.trim());
      button.textContent = button.getAttribute('data-copied');
      setTimeout(() => { button.textContent = label; }, 1800);
    } catch {
      const range = document.createRange();
      range.selectNodeContents(target);
      const sel = getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    }
  });
});

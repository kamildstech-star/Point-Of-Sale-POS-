/* Runtime copied into the generated, self-contained Blade asset. */
(() => {
  if (window.POSRemixIcons) return;
  const ns = 'http://www.w3.org/2000/svg';
  const iconLabels = { close: 'Close', menu: 'Menu', search: 'Search', edit: 'Edit', delete: 'Delete', add: 'Add', minus: 'Decrease quantity', previous: 'Previous', next: 'Next', fullscreen: 'Full screen', 'fullscreen-exit': 'Exit full screen', pos: 'Cart' };
  function symbol(name) {
    const svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'currentColor');
    svg.setAttribute('width', '1em'); svg.setAttribute('height', '1em');
    svg.setAttribute('aria-hidden', 'true'); svg.setAttribute('focusable', 'false');
    svg.classList.add('remix-icon');
    svg.innerHTML = remixPaths[name];
    return svg;
  }
  function actionName(text) {
    for (const [pattern, name] of [
      [/^(×|✕|x|close|cancel)$/i, 'close'], [/^(‹|previous|prev)$/i, 'previous'], [/^(›|next)$/i, 'next'],
      [/^\+$/, 'add'], [/^[-−]$/, 'minus'], [/\bcart\b/i, 'pos'],
      [/^(add|new|create)\b/i, 'add'], [/^(edit|update)\b/i, 'edit'],
      [/^(delete|remove)\b/i, 'delete'], [/^(save|submit)\b/i, 'save'],
      [/^(search|find)\b/i, 'search'], [/^(print)\b/i, 'print'],
      [/^(export|download|csv|excel|pdf)\b/i, 'download'], [/^copy\b/i, 'copy'],
      [/^(upload|import)\b/i, 'upload'], [/^(log out|logout|sign out)$/i, 'logout'],
      [/^(log in|login|sign in)$/i, 'login'], [/^(view|more info|details)\b/i, 'view'],
    ]) if (pattern.test(text)) return name;
    return null;
  }
  function labelControl(element, name) {
    const control = element.closest('button, a, summary, [role="button"]');
    if (control?.textContent.trim()) control.classList.add('remix-control-with-text');
    if (control && (!control.hasAttribute('aria-label') || control.hasAttribute('data-remix-auto-label')) && !control.textContent.trim() && !control.querySelector('img[alt]')) {
      const label = control.getAttribute('data-card-widget') === 'collapse'
        ? (name === 'add' ? 'Expand panel' : 'Collapse panel')
        : iconLabels[name] || name.replaceAll('-', ' ');
      control.setAttribute('aria-label', label); control.setAttribute('data-remix-auto-label', 'true');
    }
  }
  function render(element) {
    if (!(element instanceof Element)) return;
    const tag = element.tagName.toLowerCase();
    if (!['i', 'svg'].includes(tag) && !element.hasAttribute('data-remix-icon')) return;
    const navigation = element.classList.contains('nav-icon') ? element.closest('.nav-link')?.textContent.trim() : null;
    const navName = navigation && [
      [/dashboard|overview/i, 'overview'], [/school|businesses/i, 'businesses'],
      [/product/i, 'product'], [/size/i, 'tag'], [/stock|inventory/i, 'inventory'],
      [/permission/i, 'reserve'], [/user|team|role/i, 'team'], [/dealer|supplier|purchas/i, 'purchasing'],
      [/return/i, 'returns'], [/order|sale/i, 'sales'], [/report/i, 'reports'], [/setting/i, 'settings'],
    ].find(([pattern]) => pattern.test(navigation))?.[1];
    const alias = [...element.classList].map(value => remixAliases[value]).find(Boolean);
    const styleName = !element.classList.contains('far') && ['star', 'heart', 'circle', 'bookmark'].includes(alias) ? `${alias}-fill` : alias;
    const name = element.getAttribute('data-remix-icon') || navName || styleName;
    if (!name || !remixPaths[name] || element.getAttribute('data-remix-rendered') === name) return;
    // Mark before changing children/classes so our observer remains idempotent.
    element.setAttribute('data-remix-rendered', name);
    if (tag === 'svg') {
      element.setAttribute('viewBox', '0 0 24 24'); element.setAttribute('fill', 'currentColor');
      element.removeAttribute('stroke'); element.innerHTML = remixPaths[name];
      element.classList.add('remix-icon');
      if (name === 'loader') element.classList.add('remix-icon-spin');
    } else {
      element.classList.add('remix-icon-holder'); element.replaceChildren(symbol(name));
    }
    labelControl(element, name);
  }
  function decorateButton(element) {
    if (!element.matches('button, a.btn, a.button, a.button-link, [role="button"]') || element.querySelector('svg, i, img')) return;
    const text = element.textContent.trim(); const name = actionName(text);
    if (!name) return;
    const svg = symbol(name); svg.classList.add('remix-button-icon');
    if (/^[×✕x‹›+−-]$/.test(text)) {
      element.replaceChildren(svg);
      if (!element.hasAttribute('aria-label')) element.setAttribute('aria-label', iconLabels[name] || name);
    } else element.prepend(svg);
  }
  function scan(root) {
    if (!(root instanceof Element) && root !== document) return;
    if (root instanceof Element) { render(root); decorateButton(root); }
    for (const element of root.querySelectorAll('i, svg, [data-remix-icon]')) render(element);
    for (const element of root.querySelectorAll('button, a.btn, a.button, a.button-link, [role="button"]')) decorateButton(element);
  }
  function start() {
    scan(document);
    new MutationObserver(changes => {
      for (const change of changes) {
        if (change.type === 'attributes') render(change.target);
        else {
          for (const node of change.addedNodes) scan(node);
          if (change.target instanceof Element) decorateButton(change.target);
        }
      }
    }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'data-remix-icon'] });
  }
  window.POSRemixIcons = { scan };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true }); else start();
})();

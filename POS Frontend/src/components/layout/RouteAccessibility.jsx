import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';

// Focus only after an intentional page/business change. Initial entry leaves
// scanner/form focus intact; filters and pagination do not move reading focus.
export default function RouteAccessibility({ businessId, pageLabel }) {
  const { pathname } = useLocation();
  const previous = useRef(`${businessId}:${pathname}`);
  useEffect(() => {
    const key = `${businessId}:${pathname}`;
    let moveFocus = previous.current !== key;
    previous.current = key;
    const content = document.getElementById('workspace-content');
    if (!content) return;
    document.title = `${pageLabel} · Universal POS`;
    const observer = new MutationObserver(update);
    // A user who starts working while a lazy page loads keeps their focus.
    function cancelFocus() { moveFocus = false; }
    function update() {
      const heading = [...content.querySelectorAll('main h1')].find(node => !node.closest('[aria-busy="true"], [hidden], [inert], [aria-hidden="true"]') &&
        ![...ancestors(node, content)].some(parent => parent.style.display === 'none'));
      if (!heading) return;
      document.title = `${heading.textContent.trim()} · Universal POS`;
      if (moveFocus && !document.querySelector('[role="dialog"][aria-modal="true"]')) {
        heading.tabIndex = -1;
        heading.focus({ preventScroll: true });
        window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
      }
      observer.disconnect();
      document.removeEventListener('pointerdown', cancelFocus, true);
      document.removeEventListener('keydown', cancelFocus, true);
    }
    observer.observe(content, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['aria-busy', 'style'] });
    document.addEventListener('pointerdown', cancelFocus, true);
    document.addEventListener('keydown', cancelFocus, true);
    update();
    return () => {
      observer.disconnect();
      document.removeEventListener('pointerdown', cancelFocus, true);
      document.removeEventListener('keydown', cancelFocus, true);
    };
  }, [businessId, pathname, pageLabel]);
  return null;
}

function* ancestors(node, until) {
  for (let parent = node; parent && parent !== until; parent = parent.parentElement) yield parent;
}

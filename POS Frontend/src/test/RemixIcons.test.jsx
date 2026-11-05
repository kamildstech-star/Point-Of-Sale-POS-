import fs from 'node:fs';
import path from 'node:path';
import { JSDOM } from 'jsdom';
import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import Icon from '../components/Icon';
import { icons } from '../icons/catalog';

const bridge = fs.readFileSync(path.resolve(process.cwd(), '../POS backend/public/asset/js/remix-icons.js'), 'utf8');
async function legacy(markup, verify) {
  const dom = new JSDOM(`<!doctype html><html><body>${markup}</body></html>`, { runScripts: 'outside-only' });
  const { window } = dom;
  try {
    window.eval(bridge);
    await new Promise(resolve => window.setTimeout(resolve, 0));
    await verify(window.document, window);
  } finally { window.close(); }
}

describe('shared Remix icon integration', () => {
  it('preserves the accessible label of icon-only actions and uses official Remix artwork', () => {
    render(<button aria-label="Edit product"><Icon name="edit" /></button>);
    const button = screen.getByRole('button', { name: 'Edit product' });
    const svg = button.querySelector('svg');
    expect(svg).toHaveAttribute('aria-hidden', 'true'); expect(svg).toHaveAttribute('focusable', 'false');
    expect(svg).toHaveAttribute('data-remix-icon', 'edit');
    const expected = document.createElement('div'); expected.innerHTML = renderToStaticMarkup(createElement(icons.edit));
    expect(svg.querySelector('path').getAttribute('d')).toBe(expected.querySelector('path').getAttribute('d'));
  });

  it('keeps legacy widget hooks and updates the artwork when collapse/expand classes change', async () => {
    await legacy('<button data-card-widget="collapse"><i id="toggle" class="fas fa-minus nav-icon"></i></button>', async (doc, win) => {
      const icon = doc.getElementById('toggle'); const button = doc.querySelector('button');
      expect(icon).toHaveClass('fas', 'fa-minus', 'nav-icon'); expect(icon.getAttribute('data-remix-rendered')).toBe('minus');
      expect(button.getAttribute('data-card-widget')).toBe('collapse');
      expect(button.getAttribute('aria-label')).toBe('Collapse panel');
      const path = icon.querySelector('path').getAttribute('d');
      icon.classList.replace('fa-minus', 'fa-plus');
      await new Promise(resolve => win.setTimeout(resolve, 0));
      expect(icon.getAttribute('data-remix-rendered')).toBe('add'); expect(icon.querySelector('path').getAttribute('d')).not.toBe(path);
      expect(button.getAttribute('aria-label')).toBe('Expand panel');
      expect(icon.querySelectorAll('svg')).toHaveLength(1);
    });
  });

  it('handles dynamically inserted controls and leaves their event handlers working', async () => {
    await legacy('<div id="target"></div>', async (doc, win) => {
      const button = doc.createElement('button'); button.textContent = 'Save changes'; let clicks = 0;
      button.addEventListener('click', () => clicks++); doc.getElementById('target').append(button);
      await new Promise(resolve => win.setTimeout(resolve, 0));
      expect(button.textContent).toBe('Save changes'); expect(button.querySelectorAll('svg')).toHaveLength(1);
      button.click(); expect(clicks).toBe(1);
      win.POSRemixIcons.scan(doc); expect(button.querySelectorAll('svg')).toHaveLength(1);
    });
  });

  it('replaces storefront symbols while preserving sizing, labels and branded graphics', async () => {
    await legacy('<a href="/search"><svg id="search" class="icon icon-search" width="18" height="18" aria-hidden="true"><path d="M0 0" /></svg><span class="visually-hidden">Search products</span></a><svg id="logout" data-remix-icon="logout"><path d="M0 0" /></svg><svg id="brand" class="admin-logo"><path d="M1 1" /></svg>', async doc => {
      const svg = doc.getElementById('search'); expect(svg.getAttribute('viewBox')).toBe('0 0 24 24');
      expect(svg.getAttribute('width')).toBe('18'); expect(svg.getAttribute('data-remix-rendered')).toBe('search');
      expect(doc.querySelector('a').textContent).toBe('Search products');
      expect(doc.getElementById('logout').getAttribute('data-remix-rendered')).toBe('logout');
      expect(doc.getElementById('brand').querySelector('path').getAttribute('d')).toBe('M1 1');
    });
  });

  it('labels a glyph-only close control and avoids duplicate artwork on repeated scans', async () => {
    await legacy('<button data-dismiss="modal">×</button>', async (doc, win) => {
      const button = doc.querySelector('button'); expect(button.getAttribute('aria-label')).toBe('Close');
      expect(button.getAttribute('data-dismiss')).toBe('modal'); expect(button.textContent).toBe('');
      win.POSRemixIcons.scan(doc); win.POSRemixIcons.scan(doc); expect(button.querySelectorAll('svg')).toHaveLength(1);
    });
  });

  it('preserves filled and outlined rating/favorite states in legacy markup', async () => {
    await legacy('<i id="filled" class="fas fa-star"></i><i id="outline" class="far fa-star"></i>', async doc => {
      expect(doc.getElementById('filled').getAttribute('data-remix-rendered')).toBe('star-fill');
      expect(doc.getElementById('outline').getAttribute('data-remix-rendered')).toBe('star');
      expect(doc.getElementById('filled').querySelector('path').getAttribute('d')).not.toBe(doc.getElementById('outline').querySelector('path').getAttribute('d'));
    });
  });
});

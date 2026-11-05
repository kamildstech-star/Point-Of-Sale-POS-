# Remix Icon integration

The React workspace and legacy Blade application use [Remix Icon](https://github.com/Remix-Design/RemixIcon). The official `@remixicon/react` package is pinned to **4.9.0**. SVG artwork is bundled/served locally; displaying icons does not require an icon CDN or webfont download.

## Coverage

- Main and retail navigation, setup tabs and overview/business cards.
- Product and variant edit/delete controls.
- Add, edit, save, cancel, search, pagination, upload/export, approval, stock, purchasing, POS, cash, reporting, billing and workspace action buttons.
- Popup close controls and busy indicators, including reduced-motion handling.
- React Toastify status icons and close buttons.
- Legacy Blade navigation, toolbars, table actions, storefront controls and toast status graphics.
- Icons inserted or changed by AdminLTE and other existing JavaScript widgets.

Text labels, accessible names, permissions, API payloads and action handlers are retained. Decorative SVGs are hidden from assistive technology; icon-only controls keep an accessible label. Filled and outlined legacy rating/favorite states remain distinct. Existing branded logos and hidden SVG definitions are preserved.

## React usage

Use the shared component rather than adding another icon library or copying SVG paths:

```jsx
import Icon from '../components/Icon';

<button type="button"><Icon name="add" />Add product</button>
<button type="button" aria-label="Edit product"><Icon name="edit" /></button>
```

`src/icons/catalog.js` contains named imports and semantic names such as `product`, `inventory`, `edit`, `delete`, `save`, `close`, `previous`, `next`, `success` and `warning`. Only the approved registry is shipped; the entire upstream icon collection is not imported through a namespace lookup. Add new icons to this registry when needed.

`SaveForm` selects an icon for its submit label with `iconForAction()` and uses a busy indicator while saving. `FormDialog` applies the same helper to its trigger. `RecordActions` keeps its existing accessible edit/delete labels and confirmation behavior. Use `spin` only for a busy/loading indicator; the icon stylesheet disables that motion when reduced motion is requested.

## Blade compatibility

`partials/remix-icons.blade.php` loads the local assets from `public/asset/js/remix-icons.js` and `public/asset/css/remix-icons.css`. Shared layouts and standalone pages with icon controls include this partial. The legacy adapter renders icons after its deferred script loads.

Existing `fa-*`/`ion-*` classes remain as **widget hooks**. They no longer require Font Awesome/Ionicons artwork for the mapped controls. The adapter inserts official Remix SVGs while retaining the original DOM elements, CSS hooks, data attributes and click listeners. It also replaces recognized storefront SVG symbols while retaining their sizing and labels.

A mutation observer handles newly inserted icons and class changes, including the minus/plus transition on actual AdminLTE collapse buttons. Repeated scans are idempotent. Automatically supplied labels for icon-only collapse controls change between “Collapse panel” and “Expand panel”; explicit application labels take precedence.

For new Blade markup, an explicit icon can be requested without an old class:

```html
<button type="button" aria-label="Edit product">
  <i data-remix-icon="edit"></i>
</button>
```

`src/icons/legacy.js` maps existing classes to registry names. The compatibility layer adds icons to recognized text action buttons while preserving their text and event handlers. Font Awesome stylesheet links were removed from the application Blade views; vendor bundles themselves were not edited. Plain print/email templates without interactive icons keep their existing layout.

## Asset generation and maintenance

From `POS Frontend`:

```powershell
npm.cmd ci
npm.cmd run build
```

The build runs `icons:sync` first. It renders the same official React icons into the self-contained Blade compatibility asset, generates legacy styling/toast graphics and copies the upstream license. The generated assets are committed with the application so Laravel can serve them without Node at runtime.

To regenerate only the Blade assets:

```powershell
npm.cmd run icons:sync
```

Edit `src/icons/catalog.js`, `src/icons/legacy.js` or `scripts/legacy-icon-runtime.js`, then regenerate. Do not hand-edit the generated files. If upgrading the pinned icon package, retain the corresponding official license in `scripts/licenses/remix-icon-VERSION.txt` before rebuilding. The deployed copy is `POS backend/public/asset/licenses/remix-icon.txt`.

No database migration or environment change is needed. See [the verification record](audit/remix-icons-verification.md) for regression and browser results.

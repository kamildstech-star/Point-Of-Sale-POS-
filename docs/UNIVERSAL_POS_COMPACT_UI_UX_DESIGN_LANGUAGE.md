# Universal POS — Compact UI/UX Theme & Design Language

**Version:** 1.0  
**Date:** 2026-11-04  
**Design language:** Compact Enterprise  
**Default appearance:** Light, with an optional dark theme  
**Application stack:** React.js with JavaScript/JSX, Tailwind CSS utilities, existing shadcn-style primitives, Inter, and Remix icons

This document defines how the Universal POS interface should look, feel, communicate and respond. It is a design reference for developers, designers and reviewers. Use it when creating a page, extending a shared component or reviewing a UI change.

The subsequent [UX-1 refinement](UX1_REFINEMENT.md) adds grouped navigation, sidebar business selection and clearer dashboard hierarchy while retaining this theme and density system.

The [UX-2 core refinement](UX2_REFINEMENT.md) adds shared server-pagination summaries, filter chips, sticky list headers, grouped product/stock/order forms and validation-aware optional sections.

The [UX-3 cashier refinement](UX3_REFINEMENT.md) adds safe search focus/F2, a persistent payment amount/action, mobile payment anchoring and explicit quote/transaction states while retaining the existing checkout protocol.

The [UX-4 dashboard refinement](UX4_REFINEMENT.md) shares a permission/capability registry between requests and rendering, exposes existing stock/purchasing/accounting summaries to specialist and custom roles, and provides a tools-only state for viewers without metric grants.

The [UX-5 accessibility/performance refinement](UX5_REFINEMENT.md) adds page titles and safe navigation focus, asynchronous dialog focus, screen-reader pagination feedback, reduced-motion toast feedback and lazy management routes. It retains the existing theme, densities and permission boundaries.

The current implementation uses local native React primitives styled in the shadcn pattern. It does not require an additional component framework or TypeScript. See [COMPACT_UI.md](COMPACT_UI.md) for the implementation audit, measured before/after results and verification history.

Values labelled **current** describe the existing source. Rules described as **guidance** apply when building or refining a screen; they do not imply that every proposed control or workflow already exists. This document does not introduce new business features or advance pending dashboard phases.

## Contents

1. [Visual identity](#1-visual-identity)
2. [Density profiles](#2-density-profiles)
3. [Light and dark theme palette](#3-light-and-dark-theme-palette)
4. [Typography](#4-typography)
5. [Spacing, surfaces and elevation](#5-spacing-surfaces-and-elevation)
6. [Application shell and navigation](#6-application-shell-and-navigation)
7. [Buttons and action hierarchy](#7-buttons-and-action-hierarchy)
8. [Forms and validation](#8-forms-and-validation)
9. [Tables and listings](#9-tables-and-listings)
10. [Dashboard cards and charts](#10-dashboard-cards-and-charts)
11. [Dialogs, drawers and menus](#11-dialogs-drawers-and-menus)
12. [Cashier and checkout experience](#12-cashier-and-checkout-experience)
13. [Loading, feedback and notifications](#13-loading-feedback-and-notifications)
14. [Icons and visual states](#14-icons-and-visual-states)
15. [UI writing and language](#15-ui-writing-and-language)
16. [Responsive behavior and accessibility](#16-responsive-behavior-and-accessibility)
17. [Developer implementation patterns](#17-developer-implementation-patterns)
18. [Page composition recipes](#18-page-composition-recipes)
19. [Review and acceptance checklist](#19-review-and-acceptance-checklist)
20. [Source of truth and maintenance](#20-source-of-truth-and-maintenance)

## 1. Visual identity

The interface should feel orderly, readable and efficient. Users should be able to scan a list, understand a balance, find the next action and finish a transaction with little searching.

| Quality | How it appears in the interface |
| --- | --- |
| Compact | Controlled padding, shorter administrative controls, useful table density |
| Professional | Neutral surfaces, restrained accent colors, fine borders, consistent alignment |
| Readable | Clear headings, legible supporting text, visible focus, distinct financial totals |
| Predictable | The same action uses the same name, icon, position and interaction pattern |
| Responsive | Content reflows; wide tables scroll inside a named region |
| Task focused | Management screens favor information density; checkout favors speed and touch use |

Use accent colors for primary actions, active navigation, chart emphasis and meaningful states. Most surfaces should remain neutral. Avoid giving every card or action its own decorative color.

Compactness comes from consistent proportions and less repeated content. Keep complete amounts, usable controls and necessary explanations available. Prefer an expandable explanation for secondary metric definitions over a large permanent block in every KPI card.

## 2. Density profiles

### 2.1 Compact — management screens

Use Compact for dashboards, product management, inventory, purchasing, customers, suppliers, reports, team management and configuration screens that exist in the application.

### 2.2 Comfortable — the selling desk

Use Comfortable for the `/pos` checkout route and its dialogs. Maintain generous controls for product selection, quantities, discounts, hold/recall, payment entry and completing a sale.

### 2.3 Current sizing matrix

Values below are minimums or targets at a normal browser font setting. Text wrapping and larger text settings can increase element height.

| Element | Compact desktop | Comfortable checkout | Small screen / coarse pointer |
| --- | ---: | ---: | ---: |
| Standard control minimum height | 36px | 48px | 48px |
| Small control minimum height | 32px | 48px | 48px |
| Control padding | 7px 12px | 12px 16px | 12px 14px |
| Standard table row target | 44px | 56px | 56px |
| Table header target | 40px | 40px, growing as needed | Grows for content |
| Table cell padding | 3px 12px | 8px 12px | 6px 12px |
| Standard card padding | 16px | 20px | Uses the active profile |
| Form label top spacing | 12px | 16px | Uses the active profile |
| Label-to-control gap | 4px | 8px | Uses the active profile |
| Page padding | 20px 24px | 20px 24px | 16px |

The current density media rule applies at `max-width: 760px` or when the primary pointer is coarse. Navigation drawer links and specialized controls can have their own usable dimensions; these tokens do not claim every interactive element has the same height.

The cashier dashboard's prominent New Sale action remains 64px high. The checkout Complete sale action has a 52px minimum. These deliberate exceptions give frequent actions more emphasis.

### 2.4 How the profile is applied

`TenantLayout` resolves the route's density. A `data-density` attribute supplies CSS variables, while `DensityProvider` carries the same value through React context to portal dialogs. Both parts matter: a dialog mounted under `document.body` cannot inherit CSS variables from a separate page container.

Theme and density are independent. Switching between light and dark changes colors; the route's sizing profile remains the same.

## 3. Light and dark theme palette

### 3.1 Core colors — current values

Use semantic tokens instead of placing raw hex values in page components.

| Purpose | CSS token | Light | Dark |
| --- | --- | --- | --- |
| Application background | `--app-bg` | `#F5F7FB` | `#0B1220` |
| Card surface | `--app-card` | `#FFFFFF` | `#151F31` |
| Elevated surface / input | `--app-elevated` | `#FFFFFF` | `#1B2940` |
| Main text | `--app-text` | `#1E293B` | `#F1F5F9` |
| Supporting text | `--app-muted-text` | `#586980` | `#A7B4C8` |
| Surface border | `--app-border` | `#E2E8F0` | `#2A3950` |
| Control border | `--app-control-border` | `#8391A7` | `#71829B` |
| Subtle surface | `--app-subtle` | `#F1F5F9` | `#1B2940` |
| Hover surface | `--app-hover` | `#E8EEF7` | `#25364F` |
| Brand / chart accent | `--app-primary` | `#2563EB` | `#8B5CF6` |
| Filled action background | `--app-action` | `#2563EB` | `#7C3AED` |
| Filled action hover | `--app-action-hover` | `#1D4ED8` | `#6D28D9` |
| Filled action text | `--app-action-foreground` | `#FFFFFF` | `#FFFFFF` |
| Text link | `--app-link` | `#1D4ED8` | `#C4B5FD` |
| Subtle accent surface | `--app-accent-subtle` | `#EFF6FF` | `#241D3E` |
| Accent border | `--app-accent-border` | `#BFDBFE` | `#514075` |
| Focus ring | `--app-focus-ring` | `#1D4ED8` | `#A78BFA` |

The dark theme uses a brighter primary accent for chart marks and a deeper action color behind white button text. Choose the token by its purpose; these values are not interchangeable.

### 3.2 Sidebar colors

| Purpose | CSS token | Light | Dark |
| --- | --- | --- | --- |
| Background | `--app-sidebar` | `#0B1F3A` | `#0A1020` |
| Primary label | `--app-sidebar-foreground` | `#F8FAFC` | `#F8FAFC` |
| Supporting label | `--app-sidebar-muted` | `#B7C6DB` | `#A7B4C8` |
| Active item surface | `--app-sidebar-active` | `#243C5C` | `#293251` |

Keep the sidebar's dark identity in both themes. An active item has a subdued surface, clear text and a single accent indicator.

### 3.3 Semantic states

| State | Light text / background | Dark text / background | Typical use |
| --- | --- | --- | --- |
| Success | `#047857` / `#ECFDF5` | `#34D399` / `#102C29` | Confirmed completion, paid status |
| Warning | `#92400E` / `#FFFBEB` | `#FBBF24` / `#332918` | Low stock, pending attention |
| Danger | `#B91C1C` / `#FEF2F2` | `#F87171` / `#351E29` | Validation error, destructive action context |
| Information | `#1D4ED8` / `#EFF6FF` | `#93C5FD` / `#142B45` | Explanations, neutral notices |

Use the corresponding semantic border tokens with these surfaces. Filled destructive buttons use `--app-danger-action` and `--app-danger-foreground`, rather than applying a status-text color as their background.

Status must remain understandable through text or an icon as well as color. “Paid”, “Draft” and “Low stock” are meaningful labels; a green, gray or amber dot alone is insufficient.

### 3.4 Theme behavior

The current preference starts in light mode unless a dark choice is saved. An authenticated user's preference is stored per user. The root receives the `dark` class and a `data-theme` value. Use the existing theme provider and toggle; avoid adding page-specific theme state.

## 4. Typography

Use **Inter** throughout the application. The font is already installed locally. Regular content uses weights 400 and 500; headings usually use 600. Reserve 700 for selected identity elements rather than every label.

| Usage | Current token / target | Weight | Guidance |
| --- | --- | ---: | --- |
| Page title | `--app-text-title`: 1.375rem / 22px | 600 | One primary heading per page |
| Section heading | `--app-text-section`: 1rem / 16px | 600 | Separate related areas |
| Card heading | 14–16px | 500–600 | Clear without competing with the page title |
| Body / entered text | `--app-text-body`: 0.875rem / 14px | 400 | Default management text |
| Field label | `--app-text-label`: 0.8125rem / 13px | 500 | Persistent visible label |
| Table body | `--app-text-table`: 0.8125rem / 13px | 400 | Tabular figures for numeric data |
| Table heading | 12px | 600 | Clear column names |
| Supporting metadata | `--app-text-small`: 0.75rem / 12px | 400 | Dates, scope, hints and definitions |
| KPI value | `--app-text-kpi`: 1.5rem / 24px | 600 | Primary metric within its card |
| Checkout entry text | 16px | 400 | Easy reading and touch entry |
| Checkout grand total | At least 20px | 600 | Distinct from subtotal and tax |

Pixel equivalents assume the normal 16px root. The application keeps `html` at the browser's normal size and sets body typography explicitly. Do not shrink the root to make a page appear denser.

Use approximately 1.4–1.5 line height for ordinary content. Headings can be tighter. Support wrapping and browser zoom; avoid fixed-height text containers.

Financial amounts and quantities use tabular numerals. Right-align them inside tables. Keep SKU, phone and reference strings intact; a value consisting of digits is not automatically a numeric measure.

Brand storytelling on an authentication screen may use larger display typography. That exception does not set the default size for administrative pages.

## 5. Spacing, surfaces and elevation

### 5.1 Spacing scale

| Token | Value | Intended use |
| --- | ---: | --- |
| `--app-space-1` | 4px | Small label gaps, icon details |
| `--app-space-2` | 8px | Inline controls, action separation |
| `--app-space-3` | 12px | Field groups and dense sections |
| `--app-space-4` | 16px | Card padding and common grid gaps |
| `--app-space-5` | 20px | Comfortable surfaces and page spacing |
| `--app-space-6` | 24px | Larger section separation |
| `--app-space-8` | 32px | Occasional major separation |

Prefer this 4px scale for layout. Borders, optical alignment and table-row arithmetic can use smaller adjustments; the current 3px compact table-cell padding helps a 36px action fit a 44px row.

### 5.2 Surfaces

Standard cards have a 1px semantic border, an 8px radius and 16px internal padding. Comfortable cards use 20px padding. Controls also have an 8px radius.

Use space and borders to establish hierarchy before adding a shadow. Dialogs and floating panels can use stronger elevation. Avoid nesting padded cards inside padded cards when a section heading and divider would suffice.

The current subtle light shadow is `0 2px 8px rgba(11, 31, 58, .06)`; its dark equivalent uses `rgba(0, 0, 0, .16)`. Consume `--app-shadow` and `--app-dialog-shadow` so a page does not need theme-specific shadow rules.

## 6. Application shell and navigation

| Element | Current behavior / size |
| --- | --- |
| Expanded desktop sidebar | 232px |
| Collapsed desktop sidebar | 72px |
| Desktop sidebar visibility | 1280px and wider |
| Smaller-screen navigation | Focus-managed modal drawer |
| Desktop navigation row | 40px minimum |
| Navigation label / icon | 13px / 18px |
| Topbar | 58px minimum; grows for content |
| Content padding | 20px vertically, 24px horizontally on desktop |
| Mobile content padding | 16px |

The desktop sidebar is fixed at the viewport edge during page scrolling. Its height follows its contents, capped at the viewport height. A long menu scrolls internally; the footer is not stretched to the bottom of an otherwise empty screen.

The topbar is sticky and contains page context, business switching, permitted-page search, theme controls and profile actions. Its search finds authorized destinations; it should not be described as a product/customer search unless those records are actually searched.

Keep the active destination visible. Collapsed navigation needs accessible names and discoverable labels. The drawer must close safely after navigation and restore focus when dismissed.

Navigation availability follows effective permissions. A familiar role name or an attractive shortcut must not expose an unauthorized destination.

## 7. Buttons and action hierarchy

### 7.1 Variants

| Variant | Usage | Visual treatment |
| --- | --- | --- |
| `primary` | Main page or form action | Filled action color |
| `secondary` | Supporting actions and navigation controls | Neutral surface and visible border |
| `ghost` | Low-emphasis actions in suitable contexts | Quiet surface, visible hover/focus |
| `destructive` | Confirmed archive, delete or destructive submission | Destructive action background with readable text |

Prefer one primary action within an action group. A page-level Add product action and a dialog-level Create product action belong to different contexts.

### 7.2 Sizes

The shared `Button` accepts `size="sm"`, `"default"`, `"icon"` or `"touch"`. Default is the normal administrative size. Small is for secondary desktop toolbars. Touch has a 48px minimum. Icon-only buttons need the full control hit area around their 16px artwork.

Use visible labels for important actions: “Create product”, “Save changes”, “Run report”, “Complete sale”. An icon alone is appropriate for familiar row edit/delete actions when an accessible label and descriptive title are supplied.

### 7.3 Interaction states

| State | Expected behavior |
| --- | --- |
| Default | Clear label, adequate contrast, recognizable boundary |
| Hover | Subtle surface or action-color change |
| Keyboard focus | Visible ring distinct from hover |
| Busy | Prevent duplicate submission and show meaningful progress text |
| Disabled | Clearly unavailable; explain a business restriction when useful |
| Destructive confirmation | State the affected record and actual consequence |

Keep current Edit/Delete icons directly available. An overflow menu is future guidance for genuinely secondary actions when it reduces clutter; it must support keyboard and touch use.

## 8. Forms and validation

### 8.1 Structure

Start with the most common fields. Use visible labels above inputs, 4px label-to-control gaps in Compact, and a 16px column gap. Two columns suit short related fields on desktop; forms become one column on mobile.

Use readable field widths. A short code, tax percentage or quantity rarely needs an entire wide page. A description can use the full form width. Group longer forms by business meaning, such as Product details, First variant and Pricing.

Add/edit workflows reuse the same form component. Populate edit values accurately and use a title and submit label that match the operation.

### 8.2 Field rules

- Keep labels visible when a value or placeholder is present.
- Communicate required or optional status consistently. Connect help text and errors to the control.
- Use examples where a format is not obvious, such as a SKU or category slug.
- Use appropriate input modes for decimal quantities and money.
- Allow textareas to grow or scroll without hiding their contents.
- Keep entered values when a server validation request fails.
- Retain the existing client validation and Laravel validation. HTML attributes alone are insufficient.

### 8.3 Error placement

Show the validation summary at the **top of the form**, followed by field-specific messages near the affected controls. A field error should name the problem and suggest a correction where possible.

Examples:

| Situation | Useful message |
| --- | --- |
| Missing product name | Enter a product name. |
| Invalid slug | Use lowercase letters, numbers and hyphens, for example `school-uniforms`. |
| Incorrect date range | The through date must be on or after the from date. |
| Invalid quantity | Enter a quantity greater than zero. |
| Server rejects a stock operation | Show the server's safe, actionable explanation. |

Do not rely on red borders alone. Keep `aria-invalid` and associated error text. Focus the relevant invalid field or the error summary after submission, using the existing form behavior.

### 8.4 Actions

Use a consistent primary-submit-then-Cancel order. The current dialog footer keeps form actions available while the body scrolls. Submission state must prevent repeated saves and unsafe dismissal while an operation is in flight.

For a destructive action, use accurate wording. “Archive product” should explain that transaction history remains. “Delete” should not be used to imply permanent removal when the backend only deactivates a record.

## 9. Tables and listings

Tables are the main density priority. A user should scan more useful records without losing field meaning or action discoverability.

### 9.1 Layout rules

| Area | Rule |
| --- | --- |
| Header | Approximately 40px, 12px semibold labels |
| Body | 13px text, 44px ordinary Compact rows |
| Long text | Wrap or make the full value discoverable |
| Numeric figures | Right-aligned, tabular numerals, complete amounts |
| IDs, SKU, phone | Preserve original string values and leading zeroes |
| Status | Clear wording; subtle badge when the component uses one |
| Actions | Stable position, accessible names, usable hit areas |
| Borders | Fine row dividers rather than heavy grids |
| Small screens | Internal horizontal scroll and a visible overflow cue |

A row can become taller when its name wraps or its content needs space. Never impose a fixed height that clips text. Never shorten a financial amount with an ellipsis.

### 9.2 Listing controls

Use the existing search, filters, pagination, export and sorting facilities where the page supports them. Show an applied filter's meaning and a way to clear it when available. Label results with a record count or page count appropriate to the endpoint.

Compact styling does not change how many records are requested. Server-paginated lists must retain their server pagination. Displaying more rows above the fold does not mean loading every record into the browser.

### 9.3 Overflow

Use `TableViewport` for table regions. It measures overflow, adds “Scroll horizontally to view all columns.” when needed and makes the region keyboard reachable. Critical columns remain in the table.

The current shared wrapper gives direct child tables a 540px minimum width. A different table width requires a deliberate shared variant or an assessed component rule, not a hidden-column workaround.

### 9.4 Required states

| State | Presentation |
| --- | --- |
| Initial loading | Named table skeleton |
| Background refresh | Existing data with a quiet refresh indication |
| No records | Explain what belongs here and show a permitted next action |
| No matching results | Explain the active search/filter and offer a clear/reset action where supported |
| Request failure | Visible error and a safe retry when available |
| Access revoked | Remove protected content and explain the access failure |

## 10. Dashboard cards and charts

### 10.1 KPI hierarchy

A KPI card should contain a short label, optional supporting icon, a 24px value, and an authorized link when a real destination exists. Optional definitions and reporting periods can sit under “About this metric”. Overall dashboard scope and dates remain visible.

Show four KPI cards across a wide desktop when space allows. The current dashboard uses two columns below 1280px and one column on mobile. Cards within a row align while still allowing longer content to grow.

Distinguish these values explicitly:

| Data meaning | Display |
| --- | --- |
| Verified zero | A formatted zero |
| Missing or unverified measure | “Unavailable” with an explanation available |
| Data still loading | Loading state rather than a fabricated value |
| Prior comparison unavailable | Omit the percentage comparison |
| Negative result | Preserve its sign and financial definition |

Gross profit, net sales, collections and balance due are different measures. UI compactness must preserve their labels, scope and calculation basis.

### 10.2 Charts

Use a short title, useful unit/currency labels, restrained grid lines and a small, consistent color set. Preserve missing data as missing; a gap is not a zero. Comparison labels must describe an actual supplied comparison period.

Provide access to exact chart values through the current data disclosure. Show a meaningful empty/error state rather than a decorative chart with invented values. Use branch and date filters only when backed by authorized data.

### 10.3 Quick actions

Link to a real supported workflow. Filter actions by permission. Keep primary task labels recognizable across dashboard and destination: “New sale” should lead to the selling desk, and a stock alert should lead to relevant inventory data.

## 11. Dialogs, drawers and menus

Use dialogs for short additions, edits and confirmations. Use a full page or an established drawer pattern for complex multi-step work when a dialog no longer gives enough context. Do not change an existing workflow merely to fit an arbitrary surface size.

### Current dialog dimensions

| Variant | Maximum width |
| --- | ---: |
| Small | 480px |
| Medium / default | 680px |
| Large | 1040px |

The desktop dialog has a viewport-relative maximum height and a scrolling body. Mobile leaves 12px outside the dialog. The desktop header uses 12px 20px padding, and the body uses 20px horizontal padding. Headers and controls can grow on small screens.

Required interaction behavior:

1. Give the dialog a meaningful accessible title.
2. Move focus inside when it opens.
3. Keep keyboard focus inside the active dialog.
4. Make the background inactive while the dialog is open.
5. Support Escape when closing is safe.
6. Restore focus to the trigger after closing.
7. Preserve the density of the originating workflow.
8. Prevent duplicate submission and unsafe closure during a protected save.

Reuse the existing `Modal` and `FormDialog`. The application also routes the shared loading/notification hosts into the active panel so feedback remains available during modal work.

## 12. Cashier and checkout experience

Checkout uses the same brand, typography family, borders and theme as management screens, with a larger interaction profile.

| Area | Experience rule |
| --- | --- |
| Barcode / search entry | Prominent label, readable input, keyboard scanner support |
| Product results | Clear name/SKU and a generous selection target |
| Cart | Readable item names, editable quantity and discoverable remove action |
| Customer | Clear walk-in state and a searchable named-customer choice |
| Discount | Visible only when authorized; changes invalidate an existing quote |
| Quote | Show server-calculated amounts and expiration state |
| Totals | Separate subtotal, discount, tax, amount to collect and grand total |
| Hold / recall | Labelled actions with large hit areas; no hover-only dependency |
| Payment entry | Clear method, amount and reference labels |
| Complete sale | Prominent labelled action, at least 52px high |
| Completion | Explicit result and an actual receipt link |

Keep money as exact decimal data through the existing server workflow. Do not replace server calculations with display-layer arithmetic. Named-customer credit and walk-in payment requirements must continue to follow the existing business rules.

Distinguish a rejected transaction from an unknown network outcome. If checkout completion is unconfirmed, preserve the existing same-request retry behavior and explain that state. A generic “Payment failed” message can be misleading if the server may already have accepted the sale.

Held carts are draft workflow state. Their UI must not imply that stock was reserved or payment was taken merely because a cart was held.

## 13. Loading, feedback and notifications

### 13.1 Loading hierarchy

| Situation | Use |
| --- | --- |
| Application request activity | Existing passive global progress indicator |
| First list load | Table skeleton and a specific loading label |
| Form options loading | Form skeleton or a contextual loading message |
| KPI/chart load | Region-level cards or an inline state |
| Refresh with usable content | Keep the content visible with quiet feedback |
| Save / checkout submission | Busy action text and operation-specific locks |

Reuse `LoadingState` and the existing activity tracker. Its current reveal delay is 180ms and minimum visible duration is 300ms, reducing flashes on quick responses. Supported layouts are `table`, `form`, `cards` and `inline`.

Use labels such as “Loading catalog…” or “Loading register and payment options…”. Avoid blocking the entire interface for an ordinary list refresh. Respect reduced-motion preferences.

### 13.2 Notifications

Use the existing global React Toastify host and centralized messaging helpers/rules. A successful API action that already receives an automatic success toast should not also receive an extra page-level toast.

| Feedback | Main purpose |
| --- | --- |
| Success toast | Confirm a completed action |
| Error toast | Make a failed operation noticeable |
| Form summary and field errors | Explain what the user needs to correct |
| Persistent page notice | Explain a condition requiring continued attention |
| Transaction outcome panel | Communicate a completed or unconfirmed financial workflow |

Keep actionable errors available in the page/form after a short-lived toast disappears. Read requests should normally remain quiet on success. A successful refresh does not need the same prominence as a completed sale.

## 14. Icons and visual states

Use **Remix icons** through the existing `Icon` wrapper and catalog.

| Context | Artwork size |
| --- | ---: |
| Sidebar navigation | 18px |
| Action buttons and row actions | 16px |
| Supporting card icon | Approximately 18–20px |
| Badge icon | 14px in the current primitive |
| Empty-state illustration | Approximately 28–36px when appropriate |

The hit area belongs to the button, not the icon. A 16px edit icon can live inside a 36px desktop or 48px touch button.

Keep familiar meanings consistent: add, edit, delete, search, previous/next, refresh, receipt and stock. Decorative SVGs remain hidden from assistive technology; the surrounding action supplies the accessible label. Avoid mixing additional icon libraries into ordinary product screens.

## 15. UI writing and language

Write in plain, direct English. Use sentence case for buttons, field labels, notices and ordinary headings. Existing small eyebrow labels can retain their established uppercase treatment.

### 15.1 Naming

| Context | Preferred pattern | Example |
| --- | --- | --- |
| Page title | What the user manages or reviews | Products, Inventory, Reports |
| Creation trigger | Add + singular object | Add product |
| Creation submission | Create + singular object | Create product |
| Edit submission | Save + object / changes | Save product |
| Viewing action | Open / View + destination | Open receipt |
| Confirmation | Actual business effect | Archive product, Remove access |
| Retry | Specific safe action where needed | Try again, Retry same checkout |

Avoid vague labels such as “Click here”, “Proceed” or “Submit” when the outcome can be named. Keep names consistent between sidebar, page heading, dialog and success message.

### 15.2 Feedback examples

| Type | Example |
| --- | --- |
| Success | Product created successfully. |
| Empty catalog | No products yet. Add your first product to begin. |
| Search with no match | No products match this search. Try another name or clear the search. |
| Permission failure | You no longer have access to this record. |
| Unavailable metric | Verified cost accounting is unavailable. |
| Unknown checkout outcome | The checkout result is unconfirmed. Retry the same checkout. |

These are writing patterns, not instructions to replace an accurate server message with a generic one.

### 15.3 Numbers, dates and terminology

Use the tenant's currency and reporting timezone in financial contexts. Keep amount precision and rounding consistent with the server policy. Do not infer a currency from a symbol alone when ambiguity matters.

Use precise labels: “Net sales excluding tax”, “Invoice total”, “Balance due” and “Return credits” have distinct meanings. Clearly label whether a measure is current or belongs to a selected reporting period.

Allow longer names, translated labels and branch names to wrap without concealing important information. This is layout guidance; it does not claim that a complete localization system is already installed.

## 16. Responsive behavior and accessibility

### 16.1 Current layout breakpoints

| Width / input condition | Main behavior |
| --- | --- |
| 1280px and wider | Desktop sidebar; wide dashboard grid where space allows |
| Below 1280px | Navigation drawer; dashboard KPI grid generally reduces to two columns |
| 1100px and below | Dashboard detail areas can stack |
| 1050px and below | Current selling-desk columns stack |
| 900px and below | Dashboard filter areas can stack |
| 760px and below | Single-column forms/KPIs; smaller page margins; larger controls |
| Coarse primary pointer | Larger controls through the density media rule |

These are component breakpoints, not device-name assumptions. A tablet with a mouse and a touchscreen workstation can require different interaction treatment.

### 16.2 Project accessibility targets

- Keep normal text contrast at least 4.5:1 and large-text contrast at least 3:1 where the applicable text criteria are being assessed.
- Give important touch actions a generous target, normally at least 48px under the touch profile.
- Keep a visible keyboard focus indicator. The existing control focus uses a 3px outline with an offset.
- Associate labels, help text and error messages with their controls.
- Use native buttons, links, inputs and tables for their intended roles.
- Make wide table regions reachable and scrollable by keyboard when needed.
- Preserve focus management and restoration in modal work.
- Keep status understandable without color alone.
- Support text growth, content wrapping, browser zoom and reduced motion.
- Preserve complete financial values and the ability to reach all critical columns.

These are design and review targets, not a declaration of full accessibility certification.

### 16.3 Review sizes

Review 360px, 768px, 1280px, 1440px and 1920px widths. Check 100%, 125% and 200% zoom/reflow, both themes, keyboard navigation and touch input where available.

The recorded compact UI browser checks used equivalent CSS viewport/device-pixel-ratio settings for zoom reflow. A physical scanner and touchscreen check remains a separate deployment task. See the implementation report for the limits of that verification.

## 17. Developer implementation patterns

### 17.1 Theme and sizing tokens

Use existing semantic variables:

```css
/* Example for a new shared component, using the installed theme. */
.stock-summary {
  color: var(--app-text);
  background: var(--app-card);
  border: 1px solid var(--app-border);
  border-radius: var(--app-radius-card);
  padding: var(--app-card-padding);
}

.stock-summary__value {
  font-size: var(--app-text-kpi);
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}
```

The class above is an authoring example, not an additional installed component. Prefer an existing shared component when it already serves the purpose.

### 17.2 Shared controls

Paths below assume imports from an appropriate location inside `src`; adapt relative paths to the calling file.

```jsx
import { Button, TextInput, SelectInput, Badge } from './components/ui/primitives';
import Icon from './components/Icon';

function ProductToolbar({ onAdd, onRefresh, refreshing }) {
  return <div className="form-actions">
    <Button onClick={onAdd}><Icon name="add"/>Add product</Button>
    <Button variant="secondary" onClick={onRefresh} disabled={refreshing}>
      <Icon name="refresh"/>{refreshing ? 'Refreshing…' : 'Refresh'}
    </Button>
  </div>;
}

function StockStatus() {
  return <Badge variant="warning">Low stock</Badge>;
}
```

For actual forms, use `Input`, `Select`, `ValidatedControl` and `SaveForm` from `WorkspaceForm` so the control participates in the application's validation pattern. Raw primitives are appropriate building blocks; they do not provide the whole form workflow by themselves.

### 17.3 Reusable popup form

```jsx
import { FormDialog } from './components/Modal';
import { Input, SaveForm } from './components/WorkspaceForm';

function AddBrandDialog({ onSave }) {
  return <FormDialog title="Add brand" trigger="Add brand">
    <SaveForm onSave={onSave} label="Create brand">
      <Input name="name" label="Brand name"/>
      <Input name="code" label="Brand code"/>
    </SaveForm>
  </FormDialog>;
}
```

The host supplies an asynchronous `onSave(formData)` using the real API and its required fields. Keep authorization, Laravel validation, request handling and mutation consequences in their existing layers.

### 17.4 Numeric table

```jsx
import TableViewport from './components/ui/TableViewport';
import { Table } from './components/ui/primitives';

function StockTable({ rows }) {
  return <TableViewport label="Product stock">
    <Table>
      <caption className="sr-only">Product stock balances</caption>
      <thead><tr>
        <th scope="col">Product</th>
        <th scope="col">SKU</th>
        <th scope="col" className="numeric">Available</th>
        <th scope="col" className="numeric">Unit price</th>
      </tr></thead>
      <tbody>{rows.map(row => <tr key={row.id}>
        <th scope="row">{row.name}</th>
        <td>{row.sku}</td>
        <td className="numeric">{row.availableDisplay}</td>
        <td className="numeric">{row.priceDisplay}</td>
      </tr>)}</tbody>
    </Table>
  </TableViewport>;
}
```

`availableDisplay` and `priceDisplay` represent correctly formatted display values supplied by the caller. Preserve exact source amounts; do not introduce floating-point financial calculations to render this table. Add new remote-list numeric fields to the explicit `tableColumns.js` metadata where applicable.

### 17.5 Density and portals

The existing layout already provides this pattern; do not add a second provider around every page:

```jsx
const density = pathname === '/pos' ? 'comfortable' : 'compact';

return <DensityProvider value={density}>
  <div data-density={density} className="app-shell">
    {children}
  </div>
</DensityProvider>;
```

Use the existing `Modal` so its portal panel receives the same density. A custom body portal needs an explicit density decision and a full accessibility review.

### 17.6 Tailwind interoperability

Tailwind utilities are installed without introducing a second global reset. Semantic mappings such as `bg-card`, `text-card-foreground`, `border-border` and `rounded-lg` point to the current theme.

Prefer tokens/shared variants for recurring sizes. Avoid repeating arbitrary heights, font sizes and colors across pages. Do not introduce a different icon family, a duplicate theme provider or another form state layer to style one screen.

## 18. Page composition recipes

### Management list

1. Page context and a concise title.
2. One brief explanation when needed.
3. Search/filter controls and the permitted primary Add action.
4. Top-level error/loading feedback.
5. Table with complete values and stable row actions.
6. Existing pagination and count information.
7. Shared add/edit dialog, opened only when requested.

### Dashboard

1. Heading, scope and last-update metadata.
2. Authorized date/branch controls.
3. Compact KPI grid with honest availability states.
4. Charts and exact-value disclosures.
5. Alerts, approvals and useful recent records.
6. Permission-aware quick actions and real drilldowns.

### Reports

1. Report heading and purpose.
2. Readable date/branch selection.
3. Run report action and errors above the relevant form.
4. Clearly labelled financial totals and units.
5. Method/balance detail and reconciliation context where supported.

### Checkout

1. Shift context and hold/recall actions.
2. Barcode/search entry and product selection.
3. Cart with quantities and remove actions.
4. Customer, permitted discount and price selection.
5. Server quote, clear totals and payment entry.
6. Prominent Complete sale action.
7. Explicit result or safe same-request retry state, followed by receipt access when completed.

These recipes organize existing capabilities. Do not display a nonfunctional control to make a page resemble a reference design.

## 19. Review and acceptance checklist

### Visual consistency

- [ ] Management body text is approximately 14px at the normal browser setting.
- [ ] Page titles are approximately 22px and headings have a clear hierarchy.
- [ ] Compact controls target 36px; touch/checkout controls remain generous.
- [ ] Ordinary administrative table rows target 44px and expand for content.
- [ ] Sidebar labels and 18px navigation icons are consistent.
- [ ] Cards, gaps, borders and radii use shared tokens.
- [ ] Light and dark states use semantic colors.
- [ ] Grand totals remain visibly more prominent than supporting amounts.

### Interaction and content

- [ ] Add/edit actions use the established form/dialog behavior.
- [ ] Errors appear at the top of the form with field-level guidance.
- [ ] Busy state prevents duplicate mutation requests.
- [ ] Success/error feedback is accurate and not duplicated unnecessarily.
- [ ] Empty, loading, refreshing, error and unavailable states are distinguishable.
- [ ] Action names describe their actual business effects.
- [ ] Required financial context, currency and reporting scope remain available.

### Responsive and keyboard review

- [ ] Desktop, tablet and mobile layouts fit without unintended page overflow.
- [ ] Wide tables keep all critical columns reachable.
- [ ] Long names and large monetary values are readable.
- [ ] Focus is visible; modal opening, trapping, Escape and restoration work.
- [ ] Larger text and zoom do not clip labels or actions.
- [ ] Important touch workflows work without hover.
- [ ] Color is not the only state cue, and reduced motion is respected.

### Functional preservation

- [ ] Existing routes, effective permissions and tenant/branch scope are preserved.
- [ ] Server validation and financial calculations stay authoritative.
- [ ] Server pagination, filters, exports and row actions still work where available.
- [ ] No protected cost or cross-tenant data is introduced through presentation changes.
- [ ] Checkout, hold/recall and unconfirmed-result retry behavior remain intact.

## 20. Source of truth and maintenance

| Concern | Current source location |
| --- | --- |
| Colors, type and density tokens | `POS Frontend/src/styles/tokens.css` |
| General controls, forms, tables and receipts | `POS Frontend/src/styles/app.css` |
| Shared primitive styles | `POS Frontend/src/styles/foundations.css` |
| Tailwind semantic mappings | `POS Frontend/src/styles/tailwind.css` |
| Shell and navigation | `POS Frontend/src/styles/shell.css` |
| Dashboard layout and KPI hierarchy | `POS Frontend/src/styles/dashboard.css` |
| Dialog styling | `POS Frontend/src/styles/modal.css` |
| Icon sizing | `POS Frontend/src/styles/icons.css` |
| Global notifications | `POS Frontend/src/styles/notifications.css` |
| Shared loading | `POS Frontend/src/components/LoadingState.jsx` and `src/loading/` |
| Native shared primitives | `POS Frontend/src/components/ui/primitives.jsx` |
| Density context | `POS Frontend/src/components/ui/DensityProvider.jsx` |
| Accessible table overflow | `POS Frontend/src/components/ui/TableViewport.jsx` |
| Numeric column metadata | `POS Frontend/src/components/ui/tableColumns.js` |
| Validated forms | `POS Frontend/src/components/WorkspaceForm.jsx` |
| Focus-managed dialogs | `POS Frontend/src/components/Modal.jsx` |
| Theme preference | `POS Frontend/src/theme/` |

Change a shared token or component when the requirement is shared. Add a component-specific rule only when its task justifies the difference. Keep this document aligned with source changes and record deliberate exceptions.

For screenshots, measured sizing, test results and the before/after gallery, use [Compact UI implementation and verification](COMPACT_UI.md) and [the visual comparison gallery](design/compact/index.html).

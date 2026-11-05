# UI-1: light/dark foundations

The shared loading foundations now include automatic global API progress and contextual skeleton/refresh states. See [GLOBAL_LOADING.md](GLOBAL_LOADING.md).

Implemented from [the supplied design guide](UNIVERSAL_POS_UI_UX_THEME_IMPLEMENTATION_GUIDE.md) after the [UI-0 architecture audit and migration matrix](UI_FOUNDATIONS_AUDIT.md). This is the UI design track's first phase; it does not change the original implementation blueprint's backend phases.

## Using the themes

Sign in to the React workspace. The moon/sun button next to your name and Sign out switches themes:

- **Modern Enterprise Blue** is the default, including when the operating system prefers dark.
- **Premium Dark** uses the same routes, information, permissions and controls.
- Explicit preferences are stored in this browser under `universal-pos:theme:user:<authenticated-user-id>`. Different staff accounts have independent preferences; businesses belonging to the same staff account share that user's theme.
- Logout/session expiry uses the separate guest preference, defaulting to light. The theme storage contains no credentials or authorization data.
- Refresh restores the verified user's preference. An initial guest preference applies before React mounts; the authenticated preference applies in a layout effect before authenticated content paints.
- A storage event synchronizes the active user's preference across tabs. If browser storage is blocked, switching still works for the current mounted application. Cross-device/profile synchronization and an explicit system mode are future options.

Theme changes do not remount the route or forms, submit requests, change selected products, recalculate totals, or affect tenant access. An actual business switch continues to remount the tenant route subtree as before.

## Foundation implementation

- `src/styles/tokens.css`: guide identity colors plus semantic surfaces, borders, actions, text/status colors, spacing, radii, shadows and focus tokens for both themes.
- `src/styles/tailwind.css`: Tailwind 4 semantic utilities (`bg-background`, `text-foreground`, `bg-card`, `border-border`, `bg-primary`, etc.) and class-based dark variant. Preflight is omitted to preserve existing native forms/tables.
- `src/styles/app.css`, `modal.css`, `notifications.css`, `foundations.css`: shared page, control, table, dialog, error and toast presentation. Existing sidebar positioning/overflow remains in `layout.css`.
- `src/theme/`: browser-safe preference helpers, auth-user-scoped provider, accessible native button and named Remix sun/moon icons.
- `src/components/ui/primitives.jsx`: JavaScript native Button, TextInput, SelectInput, Card, Badge, Alert, Skeleton and Table. Input/Select integrate into the existing validated form layer. Existing Modal/FormDialog and RecordActions/DeleteDialog remain the dialog/confirmation foundations.
- Inter 400/500/600/700 Latin font files are served locally by Vite, with system font fallback. No Google font request is needed.
- Status primitives include text and Remix icons; skeleton animation respects reduced motion. Native controls retain their labels, IDs, disabled states, validation associations and form values.
- Toastify's existing shared host and behavior remain intact, including placement inside an active modal's accessible focus layer. Background, foreground, icons, close controls and progress derive from the root theme.

This uses **shadcn-style JavaScript foundations**, adapted to the existing application. No shadcn CLI, Radix dependency, Lucide package, TypeScript files or second form framework was introduced. Existing APIs, session/CSRF flow, decimal-string pricing, server totals, financial idempotency and tenant authorization remain unchanged. The existing icon build also regenerates local Blade Remix assets from the expanded registry; Blade's layout/theme is otherwise untouched.

## Contrast decisions

The guide identifies its colors as starting points. Preserve the identity palette while using stronger variants for small text and controls:

| Use | Light | Dark | Reason |
| --- | --- | --- | --- |
| Primary identity | `#2563EB` | `#8B5CF6` | Guide palette |
| Button surface with white text | `#2563EB` | `#7C3AED` | Approximately 5.17:1 / 5.70:1; white on the original dark accent is only 4.23:1 |
| Muted small text | `#586980` | `#A7B4C8` | Original light muted token on app background is approximately 4.44:1 |
| Native control boundary | `#8391A7` | `#71829B` | Approximately 3.19:1 / 3.73:1 against elevated surfaces |
| Focus ring | `#1D4ED8` | `#A78BFA` | Strong visible focus on app surfaces; light sidebar uses the guide's lighter ring |

The WCAG review here covers representative foundation states, not a claim that every legacy or future screen has completed an accessibility audit.

## Verification and review

Automated checks and browser evidence are recorded in [audit/ui-foundations-verification.md](audit/ui-foundations-verification.md). Screenshots use synthetic fixture data in the built React application, without database access.

| Screen | Light | Dark |
| --- | --- | --- |
| Products and shared navigation | [Light preview](design/ui1/light-products.png) | [Dark preview](design/ui1/dark-products.png) |
| Product popup and native fields | [Light dialog](design/ui1/light-dialog.png) | [Dark dialog](design/ui1/dark-dialog.png) |

Additional previews: [narrow mobile dialog](design/ui1/dark-mobile.png), [200% zoom-equivalent layout](design/ui1/dark-zoom.png).

To run locally:

```powershell
Set-Location 'POS Frontend'
npm.cmd run dev
```

If Vite was running during dependency installation, restart it once. Keep the existing Laravel development server running for real data.

## Remaining UI phases

The separately approved [dashboard D1](DASHBOARD_D1.md) now supplies the shared shell/navigation work and reusable dashboard components. This does not mark the entire earlier UI design roadmap complete. Real dashboards, checkout refinement, module layouts, billing/platform administration and complete design QA/release remain later work. No missing analytics/global business-record search/platform-admin API, live Stripe activation or migration is implied. The existing backend blueprint's staging/release gates remain separate.

Implementation references: [Tailwind Vite integration](https://tailwindcss.com/docs/installation/using-vite), [omitting Preflight](https://tailwindcss.com/docs/preflight#disabling-preflight), [semantic theme variables](https://tailwindcss.com/docs/theme), [shadcn JavaScript support](https://ui.shadcn.com/docs/javascript), [Inter self-hosting](https://fontsource.org/fonts/inter/use).

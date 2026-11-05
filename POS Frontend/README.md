# Universal POS frontend

React 18, Vite, React Router and Axios; JavaScript/JSX only. Run `npm.cmd ci`, then `npm.cmd run dev`. Open http://127.0.0.1:5173 with Laravel serving http://127.0.0.1:8000. The development proxy handles API and Sanctum paths.
If Laravel uses another port, set `POS_API_TARGET` in `.env.local` (for example, `POS_API_TARGET=http://127.0.0.1:8081`) and restart Vite. Leave `VITE_API_URL` empty to keep session requests on the frontend origin.

Open the Vite URL above. XAMPP cannot serve the source `index.html` as a working React app because JSX needs Vite's compilation. To change the frontend port, use `npm.cmd run dev -- --port 3002` with the extra `--`; `npm.cmd run dev --port 3002` makes Vite treat `3002` as its source folder and return 404. Custom frontend ports must also be allowed in Laravel's `SANCTUM_STATEFUL_DOMAINS` setting.

Use an existing staff account or register a business. React routes include sign-in, registration, business selection, catalog setup, product editing and team management. Stock and checkout components exist but are not yet connected to the app routes. Run `npm.cmd test` for component/API tests and `npm.cmd run build` for the production bundle. Read [implementation instructions](../docs/IMPLEMENTATION.md) for backend setup and [staging instructions](../docs/STAGING.md) before deployment.

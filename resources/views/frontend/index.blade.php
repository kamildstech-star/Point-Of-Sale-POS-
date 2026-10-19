@extends('layouts.frontend_master')

@section('meta')
  @include('frontend.storefront-index-tags')
@endsection

@section('css')
    <script src="{{ asset('asset/js/frontend/assets-constants.js') }}" defer></script>
    <script src="{{ asset('asset/js/frontend/assets-pubsub.js') }}" defer></script>
    <script src="{{ asset('asset/js/frontend/assets-global.js') }}" defer></script>
    <script src="{{ asset('asset/js/frontend/assets-slick.min.js') }}" defer></script>
    <script>
        window.performance && window.performance.mark && window.performance.mark('shopify.content_for_header.start');
    </script>
    <script async="async" src="{{ asset('asset/js/frontend/internal-preloads.js') }}"></script>
    <script id="shopify-features"
    type="application/json">{"accessToken":"9a0ff412ad8c9e923c8353ae3bce50e6","betas":["rich-media-storefront-analytics"],"domain":"uniformers.pk","predictiveSearch":true,"shopId":44344246432,"smart_payment_buttons_url":"https:\/\/uniformers.pk\/cdn\/shopifycloud\/payment-sheet\/assets\/latest\/spb.en.js","dynamic_checkout_cart_url":"https:\/\/uniformers.pk\/cdn\/shopifycloud\/payment-sheet\/assets\/latest\/dynamic-checkout-cart.en.js","locale":"en"}</script>
    <script>
        var Shopify = Shopify || {};
        Shopify.shop = "uniformeruniform.myshopify.com";
        Shopify.locale = "en";
        Shopify.currency = {
            "active": "PKR",
            "rate": "1.0"
        };
        Shopify.country = "PK";
        Shopify.theme = {
            "name": "20-Jul-24 Native API's custom-dev 3.0",
            "id": 136537014432,
            "schema_name": "Talha Amjad DW",
            "schema_version": "9.0.0",
            "theme_store_id": 887,
            "role": "main"
        };
        Shopify.theme.handle = "null";
        Shopify.theme.style = {
            "id": null,
            "handle": null
        };
        Shopify.cdnHost = "uniformers.pk/cdn";
        Shopify.routes = Shopify.routes || {};
        Shopify.routes.root = "/";
    </script>
    <script type="module">
        ! function(o) {
            (o.Shopify = o.Shopify || {}).modules = !0
        }(window);
    </script>
    <script>
        ! function(o) {
            function n() {
                var o = [];

                function n() {
                    o.push(Array.prototype.slice.apply(arguments))
                }
                return n.q = o, n
            }
            var t = o.Shopify = o.Shopify || {};
            t.loadFeatures = n(), t.autoloadFeatures = n()
        }(window);
    </script>
    <script id="shop-js-analytics" type="application/json">{"pageType":"index"}</script>
    <script id="__st">
        var __st = {
            "a": 44344246432,
            "offset": 18000,
            "reqid": "c9a86600-c373-445a-b6db-6f372bd69c42-1737971690",
            "pageurl": "uniformers.pk\/",
            "u": "ac2257a36c3b",
            "p": "home"
        };
    </script>
    <script>
        window.ShopifyPaypalV4VisibilityTracking = true;
    </script>
    <script id="captcha-bootstrap">
        ! function() {
            'use strict';
            const t = 'contact',
                e = 'account',
                n = 'new_comment',
                o = [
                    [t, t],
                    ['blogs', n],
                    ['comments', n],
                    [t, 'customer']
                ],
                c = [
                    [e, 'customer_login'],
                    [e, 'guest_login'],
                    [e, 'recover_customer_password'],
                    [e, 'create_customer']
                ],
                r = t => t.map((([t, e]) =>
                    `form[action*='/${t}']:not([data-nocaptcha='true']) input[name='form_type'][value='${e}']`)).join(','),
                a = t => () => t ? [...document.querySelectorAll(t)].map((t => t.form)) : [];

            function s() {
                const t = [...o],
                    e = r(t);
                return a(e)
            }
            const i = 'password',
                u = 'form_key',
                d = ['recaptcha-v3-token', 'g-recaptcha-response', 'h-captcha-response', i],
                f = () => {
                    try {
                        return window.sessionStorage
                    } catch {
                        return
                    }
                },
                m = '__shopify_v',
                _ = t => t.elements[u];

            function p(t, e, n = !1) {
                try {
                    const o = window.sessionStorage,
                        c = JSON.parse(o.getItem(e)),
                        {
                            data: r
                        } = function(t) {
                            const {
                                data: e,
                                action: n
                            } = t;
                            return t[m] || n ? {
                                data: e,
                                action: n
                            } : {
                                data: t,
                                action: n
                            }
                        }(c);
                    for (const [e, n] of Object.entries(r)) t.elements[e] && (t.elements[e].value = n);
                    n && o.removeItem(e)
                } catch (o) {
                    console.error('form repopulation failed', {
                        error: o
                    })
                }
            }
            const l = 'form_type',
                E = 'cptcha';

            function T(t) {
                t.dataset[E] = !0
            }
            const w = window,
                h = w.document,
                L = 'Shopify',
                v = 'ce_forms',
                y = 'captcha';
            let A = !1;
            ((t, e) => {
                const n = (g = 'f06e6c50-85a8-45c8-87d0-21a2b65856fe', I =
                    'https://cdn.shopify.com/shopifycloud/storefront-forms-hcaptcha/ce_storefront_forms_captcha_hcaptcha.v1.5.2.iife.js',
                    D = {
                        infoText: 'Protected by hCaptcha',
                        privacyText: 'Privacy',
                        termsText: 'Terms'
                    }, (t, e, n) => {
                        const o = w[L][v],
                            c = o.bindForm;
                        if (c) return c(t, g, e, D).then(n);
                        var r;
                        o.q.push([
                            [t, g, e, D], n
                        ]), r = I, A || (h.body.append(Object.assign(h.createElement('script'), {
                            id: 'captcha-provider',
                            async: !0,
                            src: r
                        })), A = !0)
                    });
                var g, I, D;
                w[L] = w[L] || {}, w[L][v] = w[L][v] || {}, w[L][v].q = [], w[L][y] = w[L][y] || {}, w[L][y].protect =
                    function(t, e) {
                        n(t, void 0, e), T(t)
                    }, Object.freeze(w[L][y]),
                    function(t, e, n, w, h, L) {
                        const [v, y, A, g] = function(t, e, n) {
                            const i = e ? o : [],
                                u = t ? c : [],
                                d = [...i, ...u],
                                f = r(d),
                                m = r(i),
                                _ = r(d.filter((([t, e]) => n.includes(e))));
                            return [a(f), a(m), a(_), s()]
                        }(w, h, L), I = t => {
                            const e = t.target;
                            return e instanceof HTMLFormElement ? e : e && e.form
                        }, D = t => v().includes(t);
                        t.addEventListener('submit', (t => {
                            const e = I(t);
                            if (!e) return;
                            const n = D(e) && !e.dataset.hcaptchaBound && !e.dataset.recaptchaBound,
                                o = _(e),
                                c = g().includes(e) && (!o || !o.value);
                            (n || c) && t.preventDefault(), c && !n && (function(t) {
                                try {
                                    if (!f()) return;
                                    ! function(t) {
                                        const e = f();
                                        if (!e) return;
                                        const n = _(t);
                                        if (!n) return;
                                        const o = n.value;
                                        o && e.removeItem(o)
                                    }(t);
                                    const e = Array.from(Array(32), (() => Math.random().toString(
                                        36)[2])).join('');
                                    ! function(t, e) {
                                        _(t) || t.append(Object.assign(document.createElement(
                                            'input'), {
                                            type: 'hidden',
                                            name: u
                                        })), t.elements[u].value = e
                                    }(t, e),
                                    function(t, e) {
                                        const n = f();
                                        if (!n) return;
                                        const o = [...t.querySelectorAll(`input[type='${i}']`)].map(
                                                (({
                                                    name: t
                                                }) => t)),
                                            c = [...d, ...o],
                                            r = {};
                                        for (const [a, s] of new FormData(t).entries()) c.includes(
                                            a) || (r[a] = s);
                                        n.setItem(e, JSON.stringify({
                                            [m]: 1,
                                            action: t.action,
                                            data: r
                                        }))
                                    }(t, e)
                                } catch (e) {
                                    console.error('failed to persist form', e)
                                }
                            }(e), e.submit())
                        }));
                        const S = (t, e) => {
                            t && !t.dataset[E] && (n(t, e.some((e => e === t))), T(t))
                        };
                        for (const o of ['focusin', 'change']) t.addEventListener(o, (t => {
                            const e = I(t);
                            D(e) && S(e, y())
                        }));
                        const B = e.get('form_key'),
                            M = e.get(l),
                            P = B && M;
                        t.addEventListener('DOMContentLoaded', (() => {
                            const t = y();
                            if (P)
                                for (const e of t) e.elements[l].value === M && p(e, B);
                            [...new Set([...A(), ...v().filter((t => 'true' === t.dataset
                                .shopifyCaptcha))])].forEach((e => S(e, t)))
                        }))
                    }(h, new URLSearchParams(w.location.search), n, t, e, ['guest_login'])
            })(!0, !0)
        }();
    </script>
    <script integrity="sha256-EGCDRYTvIEOXsReXgqGwkAR+5Dl8tickSrieA/ZcQwc=" data-source-attribution="shopify.loadfeatures"
        defer
        src="{{ asset('asset/js/frontend/storefront-load_feature-1060834584ef204397b1179782a1b090047ee4397cb627244ab89e03f65c4307.js') }}"
        crossorigin="anonymous"></script>
    <script integrity="sha256-HAs5a9TQVLlKuuHrahvWuke+s1UlxXohfHeoYv8G2D8="
        data-source-attribution="shopify.dynamic-checkout" defer
        src="{{ asset('asset/js/frontend/storefront-features-1c0b396bd4d054b94abae1eb6a1bd6ba47beb35525c57a217c77a862ff06d83f.js') }}"
        crossorigin="anonymous"></script>
    <script id="sections-script" data-sections="header" defer
        src="{{ asset('asset/js/frontend/compiled_assets-scripts.js') }}"></script>
    <script>
        window.performance && window.performance.mark && window.performance.mark('shopify.content_for_header.end');
    </script>
    <style data-shopify>
        @font-face {
            font-family: Montserrat;
            font-weight: 400;
            font-style: normal;
            font-display: swap;
            src: url("//uniformers.pk/cdn/fonts/montserrat/montserrat_n4.1d581f6d4bf1a97f4cbc0b88b933bc136d38d178.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=56d499827e0eb4f5ccbf96eefbdba6719341e29dddae729a7b07d57db0ce9b54") format("woff2"),
                url("//uniformers.pk/cdn/fonts/montserrat/montserrat_n4.cfce41a967758ce5a9b7d48daeb5b028fd977a9b.woff?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=0b491db4a3f03375e0cc2f0db4667c8f9f6ff16d321e2be0232a66f1336d247a") format("woff");
        }

        @font-face {
            font-family: Montserrat;
            font-weight: 700;
            font-style: normal;
            font-display: swap;
            src: url("//uniformers.pk/cdn/fonts/montserrat/montserrat_n7.c496e9cf2031deec4c4bca338faa81971c8631d4.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=56b676fae82da047d605b1a79a2dbd32da6c1afcd3da8806c68c1dec0c2ca2b5") format("woff2"),
                url("//uniformers.pk/cdn/fonts/montserrat/montserrat_n7.78b0223375c94b39ce1af7e09a0225f2bb3d05f7.woff?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=c778873126ee33a993b53a11018d1f0fbe5bf3f309718f17dd621581f8dfeac0") format("woff");
        }

        @font-face {
            font-family: Montserrat;
            font-weight: 400;
            font-style: italic;
            font-display: swap;
            src: url("//uniformers.pk/cdn/fonts/montserrat/montserrat_i4.ae02483b3d5e8777d0d4a4ccf396482c364d8955.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=f946aa7a8cfee5d51081e76fdd1655698370e1abf86d3ff6a92634d96ac42531") format("woff2"),
                url("//uniformers.pk/cdn/fonts/montserrat/montserrat_i4.ba28d1a04ec09448de486d83c63235903dfc0af8.woff?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=be6d2f8a82fbc4862e55227f74c4cf04055576b993b3b14c630abc72c03d72e9") format("woff");
        }

        @font-face {
            font-family: Montserrat;
            font-weight: 700;
            font-style: italic;
            font-display: swap;
            src: url("//uniformers.pk/cdn/fonts/montserrat/montserrat_i7.83866c3eec90071fa974c17980ffb42977f9e667.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=a0def995033d8014612e0159208a0ab0c4b752eba31904b569cacc3f04daae9b") format("woff2"),
                url("//uniformers.pk/cdn/fonts/montserrat/montserrat_i7.25524241b12d864609c85325613d60efcf1a87e3.woff?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=d13df4cdf12d7e48984321de35e7a8204399adc3e0f83a9bb867424bc0e4a19f") format("woff");
        }

        @font-face {
            font-family: Montserrat;
            font-weight: 400;
            font-style: normal;
            font-display: swap;
            src: url("//uniformers.pk/cdn/fonts/montserrat/montserrat_n4.1d581f6d4bf1a97f4cbc0b88b933bc136d38d178.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=56d499827e0eb4f5ccbf96eefbdba6719341e29dddae729a7b07d57db0ce9b54") format("woff2"),
                url("//uniformers.pk/cdn/fonts/montserrat/montserrat_n4.cfce41a967758ce5a9b7d48daeb5b028fd977a9b.woff?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=0b491db4a3f03375e0cc2f0db4667c8f9f6ff16d321e2be0232a66f1336d247a") format("woff");
        }

        @font-face {
            font-family: Montserrat;
            font-weight: 700;
            font-style: normal;
            font-display: swap;
            src: url("//uniformers.pk/cdn/fonts/montserrat/montserrat_n7.c496e9cf2031deec4c4bca338faa81971c8631d4.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=56b676fae82da047d605b1a79a2dbd32da6c1afcd3da8806c68c1dec0c2ca2b5") format("woff2"),
                url("//uniformers.pk/cdn/fonts/montserrat/montserrat_n7.78b0223375c94b39ce1af7e09a0225f2bb3d05f7.woff?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&h2=dW5pZm9ybWVycy5waw&hmac=c778873126ee33a993b53a11018d1f0fbe5bf3f309718f17dd621581f8dfeac0") format("woff");
        }

        @font-face {
            font-family: "ArialBlack";
            src: url("{{ asset('asset/fonts/ARIBLK.woff') }}") format("woff");
        }

        :root {
            --font-body-family: Montserrat, sans-serif;
            --font-body-style: normal;
            --font-body-weight: 400;
            --font-body-weight-bold: 700;

            --font-heading-family: Montserrat, sans-serif;
            --font-heading-style: normal;
            --font-heading-weight: 400;

            --font-body-scale: 1.0;
            --font-heading-scale: 1.0;

            --color-base-text: 0, 65, 107;
            --color-shadow: 0, 65, 107;
            --color-base-background-1: 255, 255, 255;
            --color-base-background-2: 243, 243, 243;
            --color-base-solid-button-labels: 255, 255, 255;
            --color-base-outline-button-labels: 18, 18, 18;
            --color-base-accent-1: 0, 65, 107;
            --color-base-accent-2: 0, 65, 107;
            --payment-terms-background-color: #ffffff;

            --gradient-base-background-1: #ffffff;
            --gradient-base-background-2: #f3f3f3;
            --gradient-base-accent-1: #00416b;
            --gradient-base-accent-2: #00416b;

            --media-padding: px;
            --media-border-opacity: 0.05;
            --media-border-width: 1px;
            --media-radius: 0px;
            --media-shadow-opacity: 0.0;
            --media-shadow-horizontal-offset: 0px;
            --media-shadow-vertical-offset: 4px;
            --media-shadow-blur-radius: 5px;
            --media-shadow-visible: 0;

            --page-width: 120rem;
            --page-width-margin: 0rem;

            --product-card-image-padding: 0.0rem;
            --product-card-corner-radius: 0.0rem;
            --product-card-text-alignment: left;
            --product-card-border-width: 0.0rem;
            --product-card-border-opacity: 0.1;
            --product-card-shadow-opacity: 0.0;
            --product-card-shadow-visible: 0;
            --product-card-shadow-horizontal-offset: 0.0rem;
            --product-card-shadow-vertical-offset: 0.4rem;
            --product-card-shadow-blur-radius: 0.5rem;

            --collection-card-image-padding: 0.0rem;
            --collection-card-corner-radius: 0.0rem;
            --collection-card-text-alignment: left;
            --collection-card-border-width: 0.0rem;
            --collection-card-border-opacity: 0.1;
            --collection-card-shadow-opacity: 0.0;
            --collection-card-shadow-visible: 0;
            --collection-card-shadow-horizontal-offset: 0.0rem;
            --collection-card-shadow-vertical-offset: 0.4rem;
            --collection-card-shadow-blur-radius: 0.5rem;

            --blog-card-image-padding: 0.0rem;
            --blog-card-corner-radius: 0.0rem;
            --blog-card-text-alignment: left;
            --blog-card-border-width: 0.0rem;
            --blog-card-border-opacity: 0.1;
            --blog-card-shadow-opacity: 0.0;
            --blog-card-shadow-visible: 0;
            --blog-card-shadow-horizontal-offset: 0.0rem;
            --blog-card-shadow-vertical-offset: 0.4rem;
            --blog-card-shadow-blur-radius: 0.5rem;

            --badge-corner-radius: 4.0rem;

            --popup-border-width: 1px;
            --popup-border-opacity: 0.1;
            --popup-corner-radius: 0px;
            --popup-shadow-opacity: 0.0;
            --popup-shadow-horizontal-offset: 0px;
            --popup-shadow-vertical-offset: 4px;
            --popup-shadow-blur-radius: 5px;

            --drawer-border-width: 1px;
            --drawer-border-opacity: 0.1;
            --drawer-shadow-opacity: 0.0;
            --drawer-shadow-horizontal-offset: 0px;
            --drawer-shadow-vertical-offset: 4px;
            --drawer-shadow-blur-radius: 5px;

            --spacing-sections-desktop: 0px;
            --spacing-sections-mobile: 0px;

            --grid-desktop-vertical-spacing: 8px;
            --grid-desktop-horizontal-spacing: 8px;
            --grid-mobile-vertical-spacing: 4px;
            --grid-mobile-horizontal-spacing: 4px;

            --text-boxes-border-opacity: 0.1;
            --text-boxes-border-width: 0px;
            --text-boxes-radius: 0px;
            --text-boxes-shadow-opacity: 0.0;
            --text-boxes-shadow-visible: 0;
            --text-boxes-shadow-horizontal-offset: 0px;
            --text-boxes-shadow-vertical-offset: 4px;
            --text-boxes-shadow-blur-radius: 5px;

            --buttons-radius: 0px;
            --buttons-radius-outset: 0px;
            --buttons-border-width: 1px;
            --buttons-border-opacity: 1.0;
            --buttons-shadow-opacity: 0.0;
            --buttons-shadow-visible: 0;
            --buttons-shadow-horizontal-offset: 0px;
            --buttons-shadow-vertical-offset: 4px;
            --buttons-shadow-blur-radius: 5px;
            --buttons-border-offset: 0px;

            --inputs-radius: 0px;
            --inputs-border-width: 1px;
            --inputs-border-opacity: 0.55;
            --inputs-shadow-opacity: 0.0;
            --inputs-shadow-horizontal-offset: 0px;
            --inputs-margin-offset: 0px;
            --inputs-shadow-vertical-offset: 4px;
            --inputs-shadow-blur-radius: 5px;
            --inputs-radius-outset: 0px;

            --variant-pills-radius: 40px;
            --variant-pills-border-width: 1px;
            --variant-pills-border-opacity: 0.55;
            --variant-pills-shadow-opacity: 0.0;
            --variant-pills-shadow-horizontal-offset: 0px;
            --variant-pills-shadow-vertical-offset: 4px;
            --variant-pills-shadow-blur-radius: 5px;
        }

        *,
        *::before,
        *::after {
            box-sizing: inherit;
        }

        html {
            box-sizing: border-box;
            font-size: calc(var(--font-body-scale) * 62.5%);
            height: 100%;
        }

        body {
            display: grid;
            grid-template-rows: auto auto 1fr auto;
            grid-template-columns: 100%;
            min-height: 100%;
            margin: 0;
            font-size: 1.5rem;
            letter-spacing: 0.06rem;
            line-height: calc(1 + 0.8 / var(--font-body-scale));
            font-family: var(--font-body-family);
            font-style: var(--font-body-style);
            font-weight: var(--font-body-weight);
        }

        @media screen and (min-width: 750px) {
            body {
                font-size: 1.6rem;
            }
        }

        @font-face {
            font-family: 'Champion Gothic Featherweight';
            src: url("{{ asset('asset/fonts/ChampionGothic-Featherweight.woff2') }}") format('woff2'),
                url("{{ asset('asset/fonts/ChampionGothic-Featherweight.woff') }}") format('woff');
            font-weight: 300;
            font-style: normal;
        }

        @font-face {
            font-family: 'Champion Gothic Bantamweight';
            src: url("{{ asset('asset/fonts/ChampionGothic-Bantamweight.woff2') }}") format('woff2'),
                url("{{ asset('asset/fonts/ChampionGothic-Bantamweight.woff') }}") format('woff');
            font-weight: 300;
            font-style: normal;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css"
        integrity="sha512-17EgCFERpgZKcm0j0fEq1YCJuyAWdz9KUtv1EjVuaOz8pDnh/0nZxmU6BBXwaaxqoi9PQXnRWqlcDB027hgv9A=="
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="{{ asset('asset/css/frontend/assets-slick.css') }}" rel="stylesheet" type="text/css" media="all">
    <link href="{{ asset('asset/css/frontend/assets-base.css') }}" rel="stylesheet" type="text/css" media="all">
    <link href="{{ asset('asset/css/frontend/assets-custom.css') }}" rel="stylesheet" type="text/css" media="all">
    <link href="{{ asset('asset/css/frontend/assets-custom-overrides.css') }}" rel="stylesheet" type="text/css"
        media="all">
    <link rel="preload" as="font"
        href="//uniformers.pk/cdn/fonts/montserrat/montserrat_n4.1d581f6d4bf1a97f4cbc0b88b933bc136d38d178.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&amp;h2=dW5pZm9ybWVycy5waw&amp;hmac=56d499827e0eb4f5ccbf96eefbdba6719341e29dddae729a7b07d57db0ce9b54"
        type="font/woff2" crossorigin>
    <link rel="preload" as="font"
        href="//uniformers.pk/cdn/fonts/montserrat/montserrat_n4.1d581f6d4bf1a97f4cbc0b88b933bc136d38d178.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&amp;h2=dW5pZm9ybWVycy5waw&amp;hmac=56d499827e0eb4f5ccbf96eefbdba6719341e29dddae729a7b07d57db0ce9b54"
        type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('asset/css/frontend/assets-component-predictive-search.css') }}"
        media="print" onload="this.media='all'">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@700;900&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"
        integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <!--AZEXO_Header_Scripts-->
    <script>
        var azh = {
            shop: 'uniformers.pk',
            device_prefixes: {
                "lg": {
                    "label": "Large device",
                    "width": false,
                    "height": false,
                    "container": 1170,
                    "min": 1200
                },
                "md": {
                    "label": "Medium device",
                    "width": 992,
                    "height": false,
                    "container": 970,
                    "max": 1199,
                    "min": 992
                },
                "sm": {
                    "label": "Small device",
                    "width": 768,
                    "height": 1150,
                    "container": 750,
                    "max": 991,
                    "min": 768
                },
                "xs": {
                    "label": "Extra small device",
                    "width": 320,
                    "height": 750,
                    "max": 767
                }
            },
        };
        var azexo = {
            purchase_extension: 'Purchase extension',
            strings: {
                addToCart: "Add to cart",
                soldOut: "Sold out",
                unavailable: "Unavailable",
                regularPrice: "Translation missing: en.products.product.regular_price",
                sale: "Sale",
                quantityMinimumMessage: "Translation missing: en.products.product.quantity_minimum_message",
                unitPrice: "Translation missing: en.products.product.unit_price_label",
                unitPriceSeparator: "Translation missing: en.general.accessibility.unit_price_separator",
                oneCartCount: "Translation missing: en.cart.popup.cart_count",
                otherCartCount: "Translation missing: en.cart.popup.cart_count",
                quantityLabel: "Translation missing: en.cart.popup.quantity_label"
            },
            moneyFormat: "Rs.{{ 123 }}"
        }
    </script>
    <style>
        .az-container {
            padding-right: 15px;
            padding-left: 15px;
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }

        @media (min-width: 768px) {
            .az-container {
                max-width: 750px;
            }
        }

        @media (min-width: 992px) {
            .az-container {
                max-width: 970px;
            }
        }

        @media (min-width: 1200px) {
            .az-container {
                max-width: 1170px;
            }
        }
    </style>
    <!--End_AZEXO_Header_Scripts--><!--AZEXO_Version_78-->
    <script src="{{ asset('asset/js/frontend/3.7.0-jquery.min.js') }}"></script>
    <script src="{{ asset('asset/js/frontend/dist-sweetalert.min.js') }}"></script>
    <script>
        document.documentElement.className = document.documentElement.className.replace('no-js', 'js');
        if (Shopify.designMode) {
            document.documentElement.classList.add('shopify-design-mode');
        }
    </script>
    <link href="https://monorail-edge.shopifysvc.com" rel="dns-prefetch">
    <script>
        (function() {
            if ("sendBeacon" in navigator && "performance" in window) {
                var session_token = document.cookie.match(/_shopify_s=([^;]*)/);

                function handle_abandonment_event(e) {
                    var entries = performance.getEntries().filter(function(entry) {
                        return /monorail-edge.shopifysvc.com/.test(entry.name);
                    });
                    if (!window.abandonment_tracked && entries.length === 0) {
                        window.abandonment_tracked = true;
                        var currentMs = Date.now();
                        var navigation_start = performance.timing.navigationStart;
                        var payload = {
                            shop_id: 44344246432,
                            url: window.location.href,
                            navigation_start,
                            duration: currentMs - navigation_start,
                            session_token: session_token && session_token.length === 2 ? session_token[1] : "",
                            page_type: "index"
                        };
                        window.navigator.sendBeacon("https://monorail-edge.shopifysvc.com/v1/produce", JSON.stringify({
                            schema_id: "online_store_buyer_site_abandonment/1.1",
                            payload: payload,
                            metadata: {
                                event_created_at_ms: currentMs,
                                event_sent_at_ms: currentMs
                            }
                        }));
                    }
                }
                window.addEventListener('pagehide', handle_abandonment_event);
            }
        }());
    </script>
    <script id="web-pixels-manager-setup">
        (function d(d, e, r, a, n) {
            var o, i, t, s, l = (i = (o = {
                modern: /Edge?\/(1{2}[4-9]|1[2-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Firefox\/(1{2}[4-9]|1[2-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Chrom(ium|e)\/(9{2}|\d{3,})\.\d+(\.\d+|)|(Maci|X1{2}).+ Version\/(15\.\d+|(1[6-9]|[2-9]\d|\d{3,})\.\d+)([,.]\d+|)( \(\w+\)|)( Mobile\/\w+|) Safari\/|Chrome.+OPR\/(9{2}|\d{3,})\.\d+\.\d+|(CPU[ +]OS|iPhone[ +]OS|CPU[ +]iPhone|CPU IPhone OS|CPU iPad OS)[ +]+(15[._]\d+|(1[6-9]|[2-9]\d|\d{3,})[._]\d+)([._]\d+|)|Android:?[ /-](12[89]|1[3-9]\d|[2-9]\d{2}|\d{4,})(\.\d+|)(\.\d+|)|Android.+Firefox\/(12[7-9]|1[3-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+Chrom(ium|e)\/(12[89]|1[3-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|SamsungBrowser\/([2-9]\d|\d{3,})\.\d+/,
                legacy: /Edge?\/(1[6-9]|[2-9]\d|\d{3,})\.\d+(\.\d+|)|Firefox\/(5[4-9]|[6-9]\d|\d{3,})\.\d+(\.\d+|)|Chrom(ium|e)\/(5[1-9]|[6-9]\d|\d{3,})\.\d+(\.\d+|)([\d.]+$|.*Safari\/(?![\d.]+ Edge\/[\d.]+$))|(Maci|X1{2}).+ Version\/(10\.\d+|(1[1-9]|[2-9]\d|\d{3,})\.\d+)([,.]\d+|)( \(\w+\)|)( Mobile\/\w+|) Safari\/|Chrome.+OPR\/(3[89]|[4-9]\d|\d{3,})\.\d+\.\d+|(CPU[ +]OS|iPhone[ +]OS|CPU[ +]iPhone|CPU IPhone OS|CPU iPad OS)[ +]+(10[._]\d+|(1[1-9]|[2-9]\d|\d{3,})[._]\d+)([._]\d+|)|Android:?[ /-](12[89]|1[3-9]\d|[2-9]\d{2}|\d{4,})(\.\d+|)(\.\d+|)|Mobile Safari.+OPR\/([89]\d|\d{3,})\.\d+\.\d+|Android.+Firefox\/(12[7-9]|1[3-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+Chrom(ium|e)\/(12[89]|1[3-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+(UC? ?Browser|UCWEB|U3)[ /]?(15\.([5-9]|\d{2,})|(1[6-9]|[2-9]\d|\d{3,})\.\d+)\.\d+|SamsungBrowser\/(5\.\d+|([6-9]|\d{2,})\.\d+)|Android.+MQ{2}Browser\/(14(\.(9|\d{2,})|)|(1[5-9]|[2-9]\d|\d{3,})(\.\d+|))(\.\d+|)|K[Aa][Ii]OS\/(3\.\d+|([4-9]|\d{2,})\.\d+)(\.\d+|)/
            }).modern, t = o.legacy, s = navigator.userAgent, i.test(s) ? "modern" : (t.test(s), "legacy"));
            window.Shopify = window.Shopify || {};
            var c = window.Shopify;
            c.analytics = c.analytics || {};
            var u = c.analytics;
            u.replayQueue = [], u.publish = function(d, e, r) {
                return u.replayQueue.push([d, e, r]), !0
            };
            try {
                self.performance.mark("wpm:start")
            } catch (d) {}
            var h = [r, "/wpm", "/b", n, l.substring(0, 1), ".js"].join("");
            ! function(d) {
                var e = d.src,
                    r = d.async,
                    a = void 0 === r || r,
                    n = d.onload,
                    o = d.onerror,
                    i = document.createElement("script"),
                    t = document.head,
                    s = document.body;
                i.async = a, i.src = e, n && i.addEventListener("load", n), o && i.addEventListener("error", o), t ? t
                    .appendChild(i) : s ? s.appendChild(i) : console.error(
                        "Did not find a head or body element to append the script")
            }({
                src: h,
                async: !0,
                onload: function() {
                    var r = window.webPixelsManager.init(d);
                    e(r);
                    var a = window.Shopify.analytics;
                    a.replayQueue.forEach((function(d) {
                        var e = d[0],
                            a = d[1],
                            n = d[2];
                        r.publishCustomEvent(e, a, n)
                    })), a.replayQueue = [], a.publish = r.publishCustomEvent, a.visitor = r.visitor
                },
                onerror: function() {
                    var e = d.storefrontBaseUrl.replace(/\/$/, ""),
                        r = "".concat(e, "/.well-known/shopify/monorail/unstable/produce_batch"),
                        n = JSON.stringify({
                            metadata: {
                                event_sent_at_ms: (new Date).getTime()
                            },
                            events: [{
                                schema_id: "web_pixels_manager_load/3.1",
                                payload: {
                                    version: a || "latest",
                                    bundle_target: l,
                                    page_url: self.location.href,
                                    status: "failed",
                                    surface: d.surface,
                                    error_msg: "".concat(h, " has failed to load")
                                },
                                metadata: {
                                    event_created_at_ms: (new Date).getTime()
                                }
                            }]
                        });
                    try {
                        if (self.navigator.sendBeacon.bind(self.navigator)(r, n)) return !0
                    } catch (d) {}
                    var o = new XMLHttpRequest;
                    try {
                        return o.open("POST", r, !0), o.setRequestHeader("Content-Type", "text/plain"), o.send(
                            n), !0
                    } catch (d) {
                        console && console.warn && console.warn(
                            "[Web Pixels Manager] Got an unhandled error while logging a load error.")
                    }
                    return !1
                }
            })
        })({
                shopId: 44344246432,
                storefrontBaseUrl: "https://uniformers.pk",
                extensionsBaseUrl: "https://extensions.shopifycdn.com/cdn/shopifycloud/web-pixels-manager",
                surface: "storefront-renderer",
                enabledBetaFlags: ["6a396365"],
                webPixelsConfigList: [{
                    "id": "563413152",
                    "configuration": "{\"config\":\"{\\\"pixel_id\\\":\\\"G-DLCPRM0H5Z\\\",\\\"target_country\\\":\\\"PK\\\",\\\"gtag_events\\\":[{\\\"type\\\":\\\"begin_checkout\\\",\\\"action_label\\\":\\\"G-DLCPRM0H5Z\\\"},{\\\"type\\\":\\\"search\\\",\\\"action_label\\\":\\\"G-DLCPRM0H5Z\\\"},{\\\"type\\\":\\\"view_item\\\",\\\"action_label\\\":[\\\"G-DLCPRM0H5Z\\\",\\\"MC-VQG8WXWEHH\\\"]},{\\\"type\\\":\\\"purchase\\\",\\\"action_label\\\":[\\\"G-DLCPRM0H5Z\\\",\\\"MC-VQG8WXWEHH\\\"]},{\\\"type\\\":\\\"page_view\\\",\\\"action_label\\\":[\\\"G-DLCPRM0H5Z\\\",\\\"MC-VQG8WXWEHH\\\"]},{\\\"type\\\":\\\"add_payment_info\\\",\\\"action_label\\\":\\\"G-DLCPRM0H5Z\\\"},{\\\"type\\\":\\\"add_to_cart\\\",\\\"action_label\\\":\\\"G-DLCPRM0H5Z\\\"}],\\\"enable_monitoring_mode\\\":false}\"}",
                    "eventPayloadVersion": "v1",
                    "runtimeContext": "OPEN",
                    "scriptVersion": "642ed32d7fafb3e0986c8ba3bf3908a1",
                    "type": "APP",
                    "apiClientId": 1780363,
                    "privacyPurposes": []
                }, {
                    "id": "267223200",
                    "configuration": "{\"pixel_id\":\"993257121211378\",\"pixel_type\":\"facebook_pixel\",\"metaapp_system_user_token\":\"-\"}",
                    "eventPayloadVersion": "v1",
                    "runtimeContext": "OPEN",
                    "scriptVersion": "8d894c63179843e74a9691414b5ad83d",
                    "type": "APP",
                    "apiClientId": 2329312,
                    "privacyPurposes": ["ANALYTICS", "MARKETING", "SALE_OF_DATA"]
                }, {
                    "id": "shopify-app-pixel",
                    "configuration": "{}",
                    "eventPayloadVersion": "v1",
                    "runtimeContext": "STRICT",
                    "scriptVersion": "0220",
                    "apiClientId": "shopify-pixel",
                    "type": "APP",
                    "privacyPurposes": ["ANALYTICS", "MARKETING"]
                }, {
                    "id": "shopify-custom-pixel",
                    "eventPayloadVersion": "v1",
                    "runtimeContext": "LAX",
                    "scriptVersion": "0220",
                    "apiClientId": "shopify-pixel",
                    "type": "CUSTOM",
                    "privacyPurposes": ["ANALYTICS", "MARKETING"]
                }],
                isMerchantRequest: false,
                initData: {
                    "shop": {
                        "name": "UNIFORMERS",
                        "paymentSettings": {
                            "currencyCode": "PKR"
                        },
                        "myshopifyDomain": "uniformeruniform.myshopify.com",
                        "countryCode": "PK",
                        "storefrontUrl": "https:\/\/uniformers.pk"
                    },
                    "customer": null,
                    "cart": null,
                    "checkout": null,
                    "productVariants": [],
                    "purchasingCompany": null
                },
            }, function pageEvents(webPixelsManagerAPI) {
                webPixelsManagerAPI.publish("page_viewed", {});
            }, "https://uniformers.pk/cdn", "61fc5d08de09438dd514a8c1c3c0ed8d7e4f4914",
            "8f4b3340w714f6111pf79f3ff6mc2383c0b", );
    </script>
    <script>
        window.ShopifyAnalytics = window.ShopifyAnalytics || {};
        window.ShopifyAnalytics.meta = window.ShopifyAnalytics.meta || {};
        window.ShopifyAnalytics.meta.currency = 'PKR';
        var meta = {
            "page": {
                "pageType": "home"
            }
        };
        for (var attr in meta) {
            window.ShopifyAnalytics.meta[attr] = meta[attr];
        }
    </script>
    <script>
        window.ShopifyAnalytics.merchantGoogleAnalytics = function() {

        };
    </script>
    <script class="analytics">
        (function() {
            var customDocumentWrite = function(content) {
                var jquery = null;

                if (window.jQuery) {
                    jquery = window.jQuery;
                } else if (window.Checkout && window.Checkout.$) {
                    jquery = window.Checkout.$;
                }

                if (jquery) {
                    jquery('body').append(content);
                }
            };

            var hasLoggedConversion = function(token) {
                if (token) {
                    return document.cookie.indexOf('loggedConversion=' + token) !== -1;
                }
                return false;
            }

            var setCookieIfConversion = function(token) {
                if (token) {
                    var twoMonthsFromNow = new Date(Date.now());
                    twoMonthsFromNow.setMonth(twoMonthsFromNow.getMonth() + 2);

                    document.cookie = 'loggedConversion=' + token + '; expires=' + twoMonthsFromNow;
                }
            }

            var trekkie = window.ShopifyAnalytics.lib = window.trekkie = window.trekkie || [];
            if (trekkie.integrations) {
                return;
            }
            trekkie.methods = [
                'identify',
                'page',
                'ready',
                'track',
                'trackForm',
                'trackLink'
            ];
            trekkie.factory = function(method) {
                return function() {
                    var args = Array.prototype.slice.call(arguments);
                    args.unshift(method);
                    trekkie.push(args);
                    return trekkie;
                };
            };
            for (var i = 0; i < trekkie.methods.length; i++) {
                var key = trekkie.methods[i];
                trekkie[key] = trekkie.factory(key);
            }
            trekkie.load = function(config) {
                trekkie.config = config || {};
                trekkie.config.initialDocumentCookie = document.cookie;
                var first = document.getElementsByTagName('script')[0];
                var script = document.createElement('script');
                script.type = 'text/javascript';
                script.onerror = function(e) {
                    var scriptFallback = document.createElement('script');
                    scriptFallback.type = 'text/javascript';
                    scriptFallback.onerror = function(error) {
                        var Monorail = {
                            produce: function produce(monorailDomain, schemaId, payload) {
                                var currentMs = new Date().getTime();
                                var event = {
                                    schema_id: schemaId,
                                    payload: payload,
                                    metadata: {
                                        event_created_at_ms: currentMs,
                                        event_sent_at_ms: currentMs
                                    }
                                };
                                return Monorail.sendRequest("https://" + monorailDomain +
                                    "/v1/produce", JSON.stringify(event));
                            },
                            sendRequest: function sendRequest(endpointUrl, payload) {
                                // Try the sendBeacon API
                                if (window && window.navigator && typeof window.navigator
                                    .sendBeacon === 'function' && typeof window.Blob ===
                                    'function' && !Monorail.isIos12()) {
                                    var blobData = new window.Blob([payload], {
                                        type: 'text/plain'
                                    });

                                    if (window.navigator.sendBeacon(endpointUrl, blobData)) {
                                        return true;
                                    } // sendBeacon was not successful

                                } // XHR beacon

                                var xhr = new XMLHttpRequest();

                                try {
                                    xhr.open('POST', endpointUrl);
                                    xhr.setRequestHeader('Content-Type', 'text/plain');
                                    xhr.send(payload);
                                } catch (e) {
                                    console.log(e);
                                }

                                return false;
                            },
                            isIos12: function isIos12() {
                                return window.navigator.userAgent.lastIndexOf(
                                        'iPhone; CPU iPhone OS 12_') !== -1 || window.navigator
                                    .userAgent.lastIndexOf('iPad; CPU OS 12_') !== -1;
                            }
                        };
                        Monorail.produce('monorail-edge.shopifysvc.com',
                            'trekkie_storefront_load_errors/1.1', {
                                shop_id: 44344246432,
                                theme_id: 136537014432,
                                app_name: "storefront",
                                context_url: window.location.href,
                                source_url: "//uniformers.pk/cdn/s/trekkie.storefront.f713401b24dad9f39ade8f88b81d533f42427af4.min.js"
                            });

                    };
                    scriptFallback.async = true;
                    scriptFallback.src =
                        '//uniformers.pk/cdn/s/trekkie.storefront.f713401b24dad9f39ade8f88b81d533f42427af4.min.js';
                    first.parentNode.insertBefore(scriptFallback, first);
                };
                script.async = true;
                script.src =
                    '//uniformers.pk/cdn/s/trekkie.storefront.f713401b24dad9f39ade8f88b81d533f42427af4.min.js';
                first.parentNode.insertBefore(script, first);
            };
            trekkie.load({
                "Trekkie": {
                    "appName": "storefront",
                    "development": false,
                    "defaultAttributes": {
                        "shopId": 44344246432,
                        "isMerchantRequest": null,
                        "themeId": 136537014432,
                        "themeCityHash": "11120136684809572722",
                        "contentLanguage": "en",
                        "currency": "PKR"
                    },
                    "isServerSideCookieWritingEnabled": true,
                    "monorailRegion": "shop_domain"
                },
                "Session Attribution": {},
                "S2S": {
                    "facebookCapiEnabled": true,
                    "source": "trekkie-storefront-renderer",
                    "apiClientId": 580111
                }
            });

            var loaded = false;
            trekkie.ready(function() {
                if (loaded) return;
                loaded = true;

                window.ShopifyAnalytics.lib = window.trekkie;


                var originalDocumentWrite = document.write;
                document.write = customDocumentWrite;
                try {
                    window.ShopifyAnalytics.merchantGoogleAnalytics.call(this);
                } catch (error) {};
                document.write = originalDocumentWrite;

                window.ShopifyAnalytics.lib.page(null, {
                    "pageType": "home"
                });

                var match = window.location.pathname.match(/checkouts\/(.+)\/(thank_you|post_purchase)/)
                var token = match ? match[1] : undefined;
                if (!hasLoggedConversion(token)) {
                    setCookieIfConversion(token);

                }
            });


            var eventsListenerScript = document.createElement('script');
            eventsListenerScript.async = true;
            eventsListenerScript.src =
                "//uniformers.pk/cdn/shopifycloud/shopify/assets/shop_events_listener-20905db421adb60b04582abab58b285362bc2e1011d17cd3eabb3bfe05798c59.js";
            document.getElementsByTagName('head')[0].appendChild(eventsListenerScript);

        })();
    </script>
    <script class="boomerang">
        (function() {
            window.BOOMR = window.BOOMR || {};
            window.BOOMR.themeName = "Talha Amjad DW";
            window.BOOMR.themeVersion = "9.0.0";
            window.BOOMR.shopId = 44344246432;
            window.BOOMR.themeId = 136537014432;
        })();
    </script>
    <script defer src="{{asset('asset/js/frontend/perf-kit-shopify-perf-kit-1.1.0.min.js')}}"
    data-application="storefront-renderer" data-shop-id="44344246432" data-render-region="gcp-us-west1"
    data-page-type="index" data-theme-instance-id="136537014432" data-monorail-region="shop_domain"
    data-resource-timing-sampling-rate="10"></script>
@endsection

@section('content')
    <main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
        <div id="shopify-section-template--16988186050720__cd18bee1-f122-4c8b-8b0d-8793519d6235" class="shopify-section">
            <section id="gx-unfrmrs-sctn-sldr" class="gx-unfrmrs-sctn-sldr">
                <div class="hrx-slider-wrap margin-bottom-template--16988186050720__cd18bee1-f122-4c8b-8b0d-8793519d6235">
                    <div class="slide hrx-slider-wrap-4d8681d8-4b70-4100-91ec-717317f9408d">
                        <style>
                            .hrx-slider-wrap-4d8681d8-4b70-4100-91ec-717317f9408d .desktop-img {
                                display: none;
                            }

                            .hrx-slider-wrap-4d8681d8-4b70-4100-91ec-717317f9408d .mobile-img,
                            .hrx-slider-wrap-4d8681d8-4b70-4100-91ec-717317f9408d .mobile-video {
                                display: block;
                            }

                            @media (min-width:750px) {
                                .hrx-slider-wrap-4d8681d8-4b70-4100-91ec-717317f9408d .mobile-img {
                                    display: none;
                                }

                                .hrx-slider-wrap-4d8681d8-4b70-4100-91ec-717317f9408d .desktop-img,
                                .hrx-slider-wrap-4d8681d8-4b70-4100-91ec-717317f9408d .mobile-video {
                                    display: block;
                                }
                            }
                        </style>
                        <div class="play-video-wrap mobile">
                            <span class="icon-play-wrap">
                                <span class="icon-play"></span>
                            </span>
                            <div class="content">
                                <h3>How to measure</h3>
                                <p>A detailed step to step guide to measure your child's height.</p>
                            </div>
                        </div>
                        <button class="close-videos">
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"
                                class="icon icon-close" fill="none" viewbox="0 0 18 17">
                                <path
                                    d="M.865 15.978a.5.5 0 00.707.707l7.433-7.431 7.579 7.282a.501.501 0 00.846-.37.5.5 0 00-.153-.351L9.712 8.546l7.417-7.416a.5.5 0 10-.707-.708L8.991 7.853 1.413.573a.5.5 0 10-.693.72l7.563 7.268-7.418 7.417z"
                                    fill="currentColor">
                                </path>
                            </svg>
                        </button>
                        <img class="mobile-img" src="{{ asset('asset/img/frontend/files-Untitled_design.gif') }}">
                        <video playsinline="playsinline" loop="loop" muted="muted" class="mobile-video hidden"
                            preload="metadata"
                            poster="//uniformers.pk/cdn/shop/files/preview_images/64153d7187724ca8b201fb63d13e1c55.thumbnail.0000000000_800x.jpg?v=1698600742">
                            <source
                                src="//uniformers.pk/cdn/shop/videos/c/vp/64153d7187724ca8b201fb63d13e1c55/64153d7187724ca8b201fb63d13e1c55.HD-1080p-2.5Mbps-20009991.mp4?v=0"
                                type="video/mp4"><img
                                src="{{ asset('asset/img/frontend/preview_images-64153d7187724ca8b201fb63d13e1c55.thumbnail.0000000000_800x.jpg') }}">
                            </source>
                        </video>
                        <div class="play-video-wrap desktop">
                            <span class="icon-play-wrap">
                                <span class="icon-play"></span>
                            </span>
                            <div class="content">
                                <h3>How to measure</h3>
                                <p>A detailed step to step guide to measure your child's height.</p>
                            </div>
                        </div>
                        <img class="desktop-img"
                            src="{{ asset('asset/img/frontend/files-pf-dc433fea--Boysgirlscombined-1.gif') }}">
                        <video playsinline="playsinline" loop="loop" muted="muted" class="slide-video hidden"
                            preload="metadata"
                            poster="//uniformers.pk/cdn/shop/files/preview_images/845c4463810544b98fc104fe6620e676.thumbnail.0000000000_800x.jpg?v=1700726294">
                            <source
                                src="//uniformers.pk/cdn/shop/videos/c/vp/845c4463810544b98fc104fe6620e676/845c4463810544b98fc104fe6620e676.HD-720p-1.6Mbps-21065603.mp4?v=0"
                                type="video/mp4"><img
                                src="images/preview_images-845c4463810544b98fc104fe6620e676.thumbnail.0000000000_800x.jpg">
                            </source>
                        </video>
                    </div>
                    <div class="slide hrx-slider-wrap-c10e44b3-b48d-44ac-876b-74dc2750374c">
                        <style>
                            .hrx-slider-wrap-c10e44b3-b48d-44ac-876b-74dc2750374c .desktop-img {
                                display: none;
                            }

                            .hrx-slider-wrap-c10e44b3-b48d-44ac-876b-74dc2750374c .mobile-img,
                            .hrx-slider-wrap-c10e44b3-b48d-44ac-876b-74dc2750374c .mobile-video {
                                display: block;
                            }

                            @media (min-width:750px) {
                                .hrx-slider-wrap-c10e44b3-b48d-44ac-876b-74dc2750374c .mobile-img {
                                    display: none;
                                }

                                .hrx-slider-wrap-c10e44b3-b48d-44ac-876b-74dc2750374c .desktop-img,
                                .hrx-slider-wrap-c10e44b3-b48d-44ac-876b-74dc2750374c .mobile-video {
                                    display: block;
                                }
                            }
                        </style>
                        <img class="mobile-img" src="{{ asset('asset/img/frontend/files-2nd_Banner.jpg') }}">
                        <img class="desktop-img"
                            src="{{ asset('asset/img/frontend/files-We_buy_our_faults-1920.jpg') }}">
                    </div>
                    <div class="slide hrx-slider-wrap-408c1778-2075-48dd-9c90-b4e8d7f72a5c">
                        <style>
                            .hrx-slider-wrap-408c1778-2075-48dd-9c90-b4e8d7f72a5c .desktop-img {
                                display: none;
                            }

                            .hrx-slider-wrap-408c1778-2075-48dd-9c90-b4e8d7f72a5c .mobile-img,
                            .hrx-slider-wrap-408c1778-2075-48dd-9c90-b4e8d7f72a5c .mobile-video {
                                display: block;
                            }

                            @media (min-width:750px) {
                                .hrx-slider-wrap-408c1778-2075-48dd-9c90-b4e8d7f72a5c .mobile-img {
                                    display: none;
                                }

                                .hrx-slider-wrap-408c1778-2075-48dd-9c90-b4e8d7f72a5c .desktop-img,
                                .hrx-slider-wrap-408c1778-2075-48dd-9c90-b4e8d7f72a5c .mobile-video {
                                    display: block;
                                }
                            }
                        </style>
                        <img class="mobile-img" src="{{ asset('asset/img/frontend/files-3rd_Banner.jpg') }}">
                        <img class="desktop-img" src="{{ asset('asset/img/frontend/files-Try_Rooms-1920_copy.jpg') }}">
                    </div>
                    <div class="slide hrx-slider-wrap-b75ed9c3-b7f0-4771-88f3-c75f18afccb1">
                        <style>
                            .hrx-slider-wrap-b75ed9c3-b7f0-4771-88f3-c75f18afccb1 .desktop-img {
                                display: none;
                            }

                            .hrx-slider-wrap-b75ed9c3-b7f0-4771-88f3-c75f18afccb1 .mobile-img,
                            .hrx-slider-wrap-b75ed9c3-b7f0-4771-88f3-c75f18afccb1 .mobile-video {
                                display: block;
                            }

                            @media (min-width:750px) {
                                .hrx-slider-wrap-b75ed9c3-b7f0-4771-88f3-c75f18afccb1 .mobile-img {
                                    display: none;
                                }

                                .hrx-slider-wrap-b75ed9c3-b7f0-4771-88f3-c75f18afccb1 .desktop-img,
                                .hrx-slider-wrap-b75ed9c3-b7f0-4771-88f3-c75f18afccb1 .mobile-video {
                                    display: block;
                                }
                            }
                        </style>
                        <img class="mobile-img" src="{{ asset('asset/img/frontend/files-4th_Banner.jpg') }}">
                        <img class="desktop-img" src="{{ asset('asset/img/frontend/files-Customer-Support-1920.jpg') }}">
                    </div>
                    <div class="slide hrx-slider-wrap-2a1b90d6-478e-4a43-adad-b3e071118ec4">
                        <style>
                            .hrx-slider-wrap-2a1b90d6-478e-4a43-adad-b3e071118ec4 .desktop-img {
                                display: none;
                            }

                            .hrx-slider-wrap-2a1b90d6-478e-4a43-adad-b3e071118ec4 .mobile-img,
                            .hrx-slider-wrap-2a1b90d6-478e-4a43-adad-b3e071118ec4 .mobile-video {
                                display: block;
                            }

                            @media (min-width:750px) {
                                .hrx-slider-wrap-2a1b90d6-478e-4a43-adad-b3e071118ec4 .mobile-img {
                                    display: none;
                                }

                                .hrx-slider-wrap-2a1b90d6-478e-4a43-adad-b3e071118ec4 .desktop-img,
                                .hrx-slider-wrap-2a1b90d6-478e-4a43-adad-b3e071118ec4 .mobile-video {
                                    display: block;
                                }
                            }
                        </style>
                        <img class="mobile-img" src="{{ asset('asset/img/frontend/files-5th_Banner.jpg') }}">
                        <img class="desktop-img" src="{{ asset('asset/img/frontend/files-Alter-free-1920.jpg') }}">
                    </div>
                </div>
            </section>
            <script>
                // Add a click event listener to elements with class .play-video-wrap
                document.querySelectorAll('.play-video-wrap').forEach(function(playButton) {
                    playButton.addEventListener('click', function() {
                        this.parentElement.classList.add("video-shown");
                        // Find the closest sibling element with class .slide-video
                        const video = findClosestSlideVideo(playButton);

                        // Check if the video is loaded and ready to play
                        if (video && video.readyState >= 2) {
                            // The value 2 indicates that the video is loaded and can be played.

                            // Remove the 'hidden' class to make the video visible
                            video.classList.remove('hidden');

                            // Play the video
                            video.play();
                        } else {
                            // Handle the case where the video is not found or not loaded yet.
                            // You can display a message or take appropriate action.
                            console.log('Video is not found or is not loaded.');
                        }
                    });
                });

                // Function to find the closest sibling with class .slide-video
                function findClosestSlideVideo(element) {
                    let sibling = element.nextElementSibling;

                    while (sibling) {
                        if (sibling.classList.contains('slide-video')) {
                            return sibling;
                        }
                        if (sibling.classList.contains('mobile-video')) {
                            return sibling;
                        }
                        sibling = sibling.nextElementSibling;
                    }
                    return null; // Return null if no matching sibling is found.
                }

                const closeBtns = document.querySelectorAll('.close-videos');

                closeBtns.forEach(function(closeBtn) {

                    closeBtn.addEventListener('click', function() {

                        const slide = this.closest('.slide');

                        slide.classList.remove('video-shown');

                        slide.querySelectorAll('video').forEach(function(video) {
                            if (!video.classList.contains('hidden')) {
                                video.classList.add('hidden');
                            }
                        });

                    });

                });
            </script>
        </div>
        <div id="shopify-section-template--16988186050720__18aeb466-bfef-4fe6-8b73-662ecc15594d" class="shopify-section">
            <style>
                .header-wrap {
                    position: relative;
                    margin-top: 50px;
                }

                .header-wrap p {
                    text-align: center;
                    font-size: 17px;
                    margin-top: -48px;
                    color: #00416B;
                }

                @media (min-width:750px) {
                    .header-wrap p {
                        margin-top: -35px;
                        font-size: 30px;
                    }
                }
            </style>
            <section id="gx-unfrmrs-sctn-shop" class="gx-unfrmrs-sctn-shop">
                <div class="gx-unfrmrs-container">
                    <div class="header-wrap">
                        <h1>what&rsquo;s your school?</h1>
                        <p>choose from below to buy uniform</p>
                    </div>
                    <div class="gx-unfrmrs-unfrm-cllctn">
                        <a href="selection-page.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-a25e4605--KIPS_2fe922e6-62a5-440d-a710-f008353b6067.png') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=MGS.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-5e460587--MGS.png') }}" alt=""
                                srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=the_educators.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-460587cf--The-Educators.png') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Lecole.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-cf03cda4--Lecole.png') }}" alt=""
                                srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Scholastic_Islamia.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-03cda4b6--Scholastic.png') }}" alt=""
                                srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Scil.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-cda4b687--SCIL.png') }}" alt=""
                                srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Lacas.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-87cf03cd--LACAS.png') }}" alt=""
                                srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Lacas_Milestone.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-87bb74ca--Milestone.png') }}" alt=""
                                srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Bricks.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-bb74cab0--Brick-School.png') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=AES.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-74cab063--AES.png') }}" alt=""
                                srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Unique.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-b0632d0d--unique_2f5d094e-53eb-4d64-a947-77ff3700b1c8.png') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Lecol_Mondial.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-632d0d33--Lecole2_69077548-1009-4c19-a154-a998d4d443c2.png') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Lahore_Alma.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-2d0d33a1--Alma_b6188658-38c5-4529-864f-8196607a114d.png') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Yousuf.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-0d33a13c--YSS_fc734d6e-1d46-4ab8-a944-3aad4592542d.png') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=American_Lycetuffs.html">
                            <img src="{{ asset('asset/img/frontend/files-1648147177_0.png') }}" alt=""
                                srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Concordia.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-a13c0872--Concordia_5a28ed1e-4162-4b11-8c05-38e988824abb.png') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Lahore_Lyceum.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-3c087216--Lahore-Lyceum_0b046a2c-25e1-46c2-a3e8-dd6e1010894f.png') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Common.html">
                            <img src="{{ asset('asset/img/frontend/files-pf-8af0e43a--The-school-shop_01bbc784-f44b-40ee-864c-8b703f68ef7b.jpg') }}"
                                alt="" srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Grand_Charter.html">
                            <img src="{{ asset('asset/img/frontend/files-images.jpg') }}" alt="" srcset=""
                                class="gx-unfrmrs-img-fluid">
                        </a>
                        <a href="uniform-selection-page?vendor=Punjab_College.html">
                            <img src="{{ asset('asset/img/frontend/files-pgc_logo-01.png') }}" alt=""
                                srcset="" class="gx-unfrmrs-img-fluid">
                        </a>
                    </div>
                </div>
            </section>

            <style>
                #shopify-section-template--16988186050720__18aeb466-bfef-4fe6-8b73-662ecc15594d .gx-unfrmrs-unfrm-cllctn a:last-child {
                    padding: 15px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
            </style>
        </div>
        <section id="shopify-section-template--16988186050720__73541401-f0b6-49ab-8f8b-1e63399104df"
            class="shopify-section">
            <link href="{{ asset('asset/css/frontend/assets-section-richtext-slider.css') }}" rel="stylesheet"
                type="text/css" media="all">

            <div class="richtext-slider-wrap page-width">
                <div class="richtext-header">
                    <h1 class="heading">why Uniformers</h1>
                    <div class="content">
                        <p>For the first time UNIFORMERS &ldquo;<strong>The School Shop&rdquo; introduces the most advanced
                                size
                                grading system, All you have to do is just to convey your child&rsquo;s height and get
                                complete
                                uniform.</strong></p>
                    </div>
                </div>

                <div class="richtext-slider">

                    <div class="richtext-slide">
                        <div class="slide-inner">
                            <div class="icon-image">
                                <img src="{{ asset('asset/img/frontend/files-Homepage-ico-1.png') }}" loading="lazy"
                                    width="197" height="197" alt="">
                            </div>
                            <h3>Alter Free Garment</h3>
                            <div class="bottom-content">
                                <p>No alteration required, however minor adjustments will be made free of cost.</p>
                            </div>
                        </div>
                    </div>

                    <div class="richtext-slide">
                        <div class="slide-inner">
                            <div class="icon-image">
                                <img src="{{ asset('asset/img/frontend/files-Homepage-1.png') }}" loading="lazy"
                                    width="153" height="149" alt="">
                            </div>
                            <h3>We Buy Our Faults</h3>
                            <div class="bottom-content">
                                <p>100% Money Back Guarantee against any manufacturing fault.</p>
                            </div>
                        </div>
                    </div>

                    <div class="richtext-slide">
                        <div class="slide-inner">
                            <div class="icon-image">
                                <img src="{{ asset('asset/img/frontend/files-Homepage-2_cd70d9aa-edf0-4588-ae2a-1b659b40094d.png') }}"
                                    loading="lazy" width="160" height="164" alt="">
                            </div>
                            <h3>Yarn to Uniform</h3>
                            <div class="bottom-content">
                                <p>Composite operations on latest machinery to ensure premium quality garment over years.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="richtext-slide">
                        <div class="slide-inner">
                            <div class="icon-image">
                                <img src="{{ asset('asset/img/frontend/files-Homepage-3.png') }}" loading="lazy"
                                    width="182" height="183" alt="">
                            </div>
                            <h3>Cooperative Staff</h3>
                            <div class="bottom-content">
                                <p>Customer friendly staff at your service anytime.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
        <div id="shopify-section-template--16988186050720__eaca7ba7-8fbb-4627-887d-6aac5b2f069f" class="shopify-section">
            <link href="{{ asset('asset/css/frontend/assets-multiple-icons-richtext.css') }}" rel="stylesheet"
                type="text/css" media="all">
            <section id="gx-unfrmrs-sctn-wwe" class="gx-unfrmrs-sctn-wwe">
                <div class="multiple-icons-richtext">
                    <h1>we are</h1>
                    <div class="icons-slider gx-unfrmrs-wwe-wrap">

                        <div class="gx-unfrmrs-wwe-award-wrap">
                            <div class="img-wrap-inner">
                                <img src="{{ asset('asset/img/frontend/files-trophy.png') }}" alt=""
                                    class="gx-unfrmrs-img-fluid">
                            </div>
                        </div>

                        <div class="gx-unfrmrs-wwe-award-wrap">
                            <div class="img-wrap-inner">
                                <img src="{{ asset('asset/img/frontend/files-puzzle.png') }}" alt=""
                                    class="gx-unfrmrs-img-fluid">
                            </div>
                        </div>

                        <div class="gx-unfrmrs-wwe-award-wrap">
                            <div class="img-wrap-inner">
                                <img src="{{ asset('asset/img/frontend/files-healthcare.png') }}" alt=""
                                    class="gx-unfrmrs-img-fluid">
                            </div>
                        </div>

                        <div class="gx-unfrmrs-wwe-award-wrap">
                            <div class="img-wrap-inner">
                                <img src="{{ asset('asset/img/frontend/files-light-bulb_1.png') }}" alt=""
                                    class="gx-unfrmrs-img-fluid">
                            </div>
                        </div>

                        <div class="gx-unfrmrs-wwe-award-wrap">
                            <div class="img-wrap-inner">
                                <img src="{{ asset('asset/img/frontend/files-team.png') }}" alt=""
                                    class="gx-unfrmrs-img-fluid">
                            </div>
                        </div>

                        <div class="gx-unfrmrs-wwe-award-wrap">
                            <div class="img-wrap-inner">
                                <img src="{{ asset('asset/img/frontend/files-handshake_3.png') }}" alt=""
                                    class="gx-unfrmrs-img-fluid">
                            </div>
                        </div>

                    </div>
                    <div class="icon-text-slider">

                        <div class="icon-text">
                            <h3>Integral</h3>
                        </div>

                        <div class="icon-text">
                            <h3>Careful</h3>
                        </div>

                        <div class="icon-text">
                            <h3>Innovative</h3>
                        </div>

                        <div class="icon-text">
                            <h3>Collaborative</h3>
                        </div>

                        <div class="icon-text">
                            <h3>Committed</h3>
                        </div>

                        <div class="icon-text">
                            <h3>Excellent</h3>
                        </div>

                    </div>
                    <div>
                        <p>Innovating professional and school-wear, UNIFORMERS has become a trusted name in 21 years,
                            serving
                            prestigious institutions across Pakistan</p>
                    </div>

                </div>
            </section>

        </div>
        <section id="shopify-section-template--16988186050720__d7c2ec8f-41f4-4478-8fc6-2c928cca0fbf"
            class="shopify-section section">
            <link href="{{ asset('asset/css/frontend/assets-section-rich-text.css') }}" rel="stylesheet" type="text/css"
                media="all">
            <style data-shopify>
                .section-template--16988186050720__d7c2ec8f-41f4-4478-8fc6-2c928cca0fbf-padding {
                    padding-top: 30px;
                    padding-bottom: 39px;
                }

                @media screen and (min-width: 750px) {
                    .section-template--16988186050720__d7c2ec8f-41f4-4478-8fc6-2c928cca0fbf-padding {
                        padding-top: 40px;
                        padding-bottom: 52px;
                    }
                }
            </style>
            <div class="isolate">
                <div
                    class="rich-text content-container color-background-1 gradient rich-text--full-width content-container--full-width section-template--16988186050720__d7c2ec8f-41f4-4478-8fc6-2c928cca0fbf-padding">
                    <div class="rich-text__wrapper rich-text__wrapper--center page-width">
                        <div class="rich-text__blocks center">
                            <h2 class="rich-text__heading rte inline-richtext h1">
                                Unmatched Customer Services
                            </h2>
                            <h2 class="rich-text__heading rte inline-richtext hxl">
                                03 111 666 123
                            </h2>
                        </div>
                    </div>
                </div>
            </div>


            <style>
                #shopify-section-template--16988186050720__d7c2ec8f-41f4-4478-8fc6-2c928cca0fbf .rich-text__heading {
                    font-weight: bold;
                    letter-spacing: -1px;
                    font-size: 19px;
                }

                #shopify-section-template--16988186050720__d7c2ec8f-41f4-4478-8fc6-2c928cca0fbf .rich-text__heading+.rich-text__heading {
                    font-family: "Champion Gothic Bantamweight";
                    font-size: 95px;
                    font-weight: 300;
                    letter-spacing: normal;
                    margin: 0;
                }

                @media (min-width: 750px) {
                    #shopify-section-template--16988186050720__d7c2ec8f-41f4-4478-8fc6-2c928cca0fbf .rich-text__heading {
                        font-size: 40px;
                        margin-top: 50px;
                    }

                    #shopify-section-template--16988186050720__d7c2ec8f-41f4-4478-8fc6-2c928cca0fbf .rich-text__heading+.rich-text__heading {
                        font-size: 200px;
                    }
                }
            </style>
        </section>
    </main>
@endsection


@section('script')
@endsection

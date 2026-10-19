<script type="text/javascript" async="" src="{{asset('asset/js/frontend/destination')}}"></script><script src="{{asset('asset/js/frontend/993257121211378')}}" async=""></script><script async="true" src="{{asset('asset/js/frontend/fbevents.js.download')}}"></script><script type="text/javascript" async="" src="{{asset('asset/js/frontend/trekkie.storefront.f713401b24dad9f39ade8f88b81d533f42427af4.min.js.download')}}"></script><script src="{{asset('asset/js/frontend/constants.js.download')}}" defer="defer"></script>
<script src="{{asset('asset/js/frontend/pubsub.js.download')}}" defer="defer"></script>
<script src="{{asset('asset/js/frontend/global.js.download')}}" defer="defer"></script>
<script src="{{asset('asset/js/frontend/slick.min.js.download')}}" defer="defer"></script>
<script>window.performance && window.performance.mark && window.performance.mark('shopify.content_for_header.start');</script>
<script async="async" src="{{asset('asset/js/frontend/preloads.js.download')}}"></script>
<script id="shopify-features" type="application/json">{"accessToken":"9a0ff412ad8c9e923c8353ae3bce50e6","betas":["rich-media-storefront-analytics"],"domain":"uniformers.pk","predictiveSearch":true,"shopId":44344246432,"smart_payment_buttons_url":"https:\/\/uniformers.pk\/cdn\/shopifycloud\/payment-sheet\/assets\/latest\/spb.en.js","dynamic_checkout_cart_url":"https:\/\/uniformers.pk\/cdn\/shopifycloud\/payment-sheet\/assets\/latest\/dynamic-checkout-cart.en.js","locale":"en"}</script>
<script>var Shopify = Shopify || {};
Shopify.shop = "uniformeruniform.myshopify.com";
Shopify.locale = "en";
Shopify.currency = {"active":"PKR","rate":"1.0"};
Shopify.country = "PK";
Shopify.theme = {"name":"20-Jul-24 Native API's custom-dev 3.0","id":136537014432,"schema_name":"Talha Amjad DW","schema_version":"9.0.0","theme_store_id":887,"role":"main"};
Shopify.theme.handle = "null";
Shopify.theme.style = {"id":null,"handle":null};
Shopify.cdnHost = "uniformers.pk/cdn";
Shopify.routes = Shopify.routes || {};
Shopify.routes.root = "/";</script>
<script type="module">!function(o){(o.Shopify=o.Shopify||{}).modules=!0}(window);</script>
<script>!function(o){function n(){var o=[];function n(){o.push(Array.prototype.slice.apply(arguments))}return n.q=o,n}var t=o.Shopify=o.Shopify||{};t.loadFeatures=n(),t.autoloadFeatures=n()}(window);</script>
<script id="shop-js-analytics" type="application/json">{"pageType":"cart"}</script>
<script id="__st">var __st={"a":44344246432,"offset":18000,"reqid":"56a514cb-8255-426a-b8ef-85e237e2fe82-1737985235","pageurl":"uniformers.pk\/cart","u":"48db36abff2f","p":"cart"};</script>
<script>window.ShopifyPaypalV4VisibilityTracking = true;</script>
<script id="captcha-bootstrap">!function(){'use strict';const t='contact',e='account',n='new_comment',o=[[t,t],['blogs',n],['comments',n],[t,'customer']],c=[[e,'customer_login'],[e,'guest_login'],[e,'recover_customer_password'],[e,'create_customer']],r=t=>t.map((([t,e])=>`form[action*='/${t}']:not([data-nocaptcha='true']) input[name='form_type'][value='${e}']`)).join(','),a=t=>()=>t?[...document.querySelectorAll(t)].map((t=>t.form)):[];function s(){const t=[...o],e=r(t);return a(e)}const i='password',u='form_key',d=['recaptcha-v3-token','g-recaptcha-response','h-captcha-response',i],f=()=>{try{return window.sessionStorage}catch{return}},m='__shopify_v',_=t=>t.elements[u];function p(t,e,n=!1){try{const o=window.sessionStorage,c=JSON.parse(o.getItem(e)),{data:r}=function(t){const{data:e,action:n}=t;return t[m]||n?{data:e,action:n}:{data:t,action:n}}(c);for(const[e,n]of Object.entries(r))t.elements[e]&&(t.elements[e].value=n);n&&o.removeItem(e)}catch(o){console.error('form repopulation failed',{error:o})}}const l='form_type',E='cptcha';function T(t){t.dataset[E]=!0}const w=window,h=w.document,L='Shopify',v='ce_forms',y='captcha';let A=!1;((t,e)=>{const n=(g='f06e6c50-85a8-45c8-87d0-21a2b65856fe',I='https://cdn.shopify.com/shopifycloud/storefront-forms-hcaptcha/ce_storefront_forms_captcha_hcaptcha.v1.5.2.iife.js',D={infoText:'Protected by hCaptcha',privacyText:'Privacy',termsText:'Terms'},(t,e,n)=>{const o=w[L][v],c=o.bindForm;if(c)return c(t,g,e,D).then(n);var r;o.q.push([[t,g,e,D],n]),r=I,A||(h.body.append(Object.assign(h.createElement('script'),{id:'captcha-provider',async:!0,src:r})),A=!0)});var g,I,D;w[L]=w[L]||{},w[L][v]=w[L][v]||{},w[L][v].q=[],w[L][y]=w[L][y]||{},w[L][y].protect=function(t,e){n(t,void 0,e),T(t)},Object.freeze(w[L][y]),function(t,e,n,w,h,L){const[v,y,A,g]=function(t,e,n){const i=e?o:[],u=t?c:[],d=[...i,...u],f=r(d),m=r(i),_=r(d.filter((([t,e])=>n.includes(e))));return[a(f),a(m),a(_),s()]}(w,h,L),I=t=>{const e=t.target;return e instanceof HTMLFormElement?e:e&&e.form},D=t=>v().includes(t);t.addEventListener('submit',(t=>{const e=I(t);if(!e)return;const n=D(e)&&!e.dataset.hcaptchaBound&&!e.dataset.recaptchaBound,o=_(e),c=g().includes(e)&&(!o||!o.value);(n||c)&&t.preventDefault(),c&&!n&&(function(t){try{if(!f())return;!function(t){const e=f();if(!e)return;const n=_(t);if(!n)return;const o=n.value;o&&e.removeItem(o)}(t);const e=Array.from(Array(32),(()=>Math.random().toString(36)[2])).join('');!function(t,e){_(t)||t.append(Object.assign(document.createElement('input'),{type:'hidden',name:u})),t.elements[u].value=e}(t,e),function(t,e){const n=f();if(!n)return;const o=[...t.querySelectorAll(`input[type='${i}']`)].map((({name:t})=>t)),c=[...d,...o],r={};for(const[a,s]of new FormData(t).entries())c.includes(a)||(r[a]=s);n.setItem(e,JSON.stringify({[m]:1,action:t.action,data:r}))}(t,e)}catch(e){console.error('failed to persist form',e)}}(e),e.submit())}));const S=(t,e)=>{t&&!t.dataset[E]&&(n(t,e.some((e=>e===t))),T(t))};for(const o of['focusin','change'])t.addEventListener(o,(t=>{const e=I(t);D(e)&&S(e,y())}));const B=e.get('form_key'),M=e.get(l),P=B&&M;t.addEventListener('DOMContentLoaded',(()=>{const t=y();if(P)for(const e of t)e.elements[l].value===M&&p(e,B);[...new Set([...A(),...v().filter((t=>'true'===t.dataset.shopifyCaptcha))])].forEach((e=>S(e,t)))}))}(h,new URLSearchParams(w.location.search),n,t,e,['guest_login'])})(!0,!0)}();</script>
<script integrity="sha256-EGCDRYTvIEOXsReXgqGwkAR+5Dl8tickSrieA/ZcQwc=" data-source-attribution="shopify.loadfeatures" defer="defer" src="{{asset('asset/js/frontend/load_feature-1060834584ef204397b1179782a1b090047ee4397cb627244ab89e03f65c4307.js.download')}}" crossorigin="anonymous"></script>
<script data-source-attribution="shopify.dynamic_checkout.dynamic.init">var Shopify=Shopify||{};Shopify.PaymentButton=Shopify.PaymentButton||{isStorefrontPortableWallets:!0,init:function(){window.Shopify.PaymentButton.init=function(){};var t=document.createElement("script");t.src="https://uniformers.pk/cdn/shopifycloud/portable-wallets/latest/portable-wallets.en.js",t.type="module",document.head.appendChild(t)}};
</script>
<script data-source-attribution="shopify.dynamic_checkout.buyer_consent">
  function portableWalletsHideBuyerConsent(e){var t=document.getElementById("shopify-buyer-consent"),n=document.getElementById("shopify-subscription-policy-button");t&&n&&(t.classList.add("hidden"),t.setAttribute("aria-hidden","true"),n.removeEventListener("click",e))}function portableWalletsShowBuyerConsent(e){var t=document.getElementById("shopify-buyer-consent"),n=document.getElementById("shopify-subscription-policy-button");t&&n&&(t.classList.remove("hidden"),t.removeAttribute("aria-hidden"),n.addEventListener("click",e))}window.Shopify?.PaymentButton&&(window.Shopify.PaymentButton.hideBuyerConsent=portableWalletsHideBuyerConsent,window.Shopify.PaymentButton.showBuyerConsent=portableWalletsShowBuyerConsent);
</script>
<script data-source-attribution="shopify.dynamic_checkout.cart.bootstrap">document.addEventListener("DOMContentLoaded",(function(){function t(){return document.querySelector("shopify-accelerated-checkout-cart, shopify-accelerated-checkout")}if(t())Shopify.PaymentButton.init();else{new MutationObserver((function(e,n){t()&&(Shopify.PaymentButton.init(),n.disconnect())})).observe(document.body,{childList:!0,subtree:!0})}}));
</script>
<script id="sections-script" data-sections="header" defer="defer" src="{{asset('asset/js/frontend/scripts.js.download')}}"></script>
<script>window.performance && window.performance.mark && window.performance.mark('shopify.content_for_header.end');</script>
    <style data-shopify="">
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
        src: url("https://cdn.shopify.com/s/files/1/0443/4424/6432/files/ARIBLK.woff?v=1689938198") format("woff");
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
          src: url(//uniformers.pk/cdn/shop/t/22/assets/ChampionGothic-Featherweight.woff2?v=182618367369495143541721599981) format('woff2'),
              url(//uniformers.pk/cdn/shop/t/22/assets/ChampionGothic-Featherweight.woff?v=176452512626593873391721599981) format('woff');
          font-weight: 300;
          font-style: normal;
      }
      @font-face {
        font-family: 'Champion Gothic Bantamweight';
          src: url(//uniformers.pk/cdn/shop/t/22/assets/ChampionGothic-Bantamweight.woff2?v=155548581489320125471721599981) format('woff2'),
              url(//uniformers.pk/cdn/shop/t/22/assets/ChampionGothic-Bantamweight.woff?v=112013870807903605651721599981) format('woff');
          font-weight: 300;
          font-style: normal;
      }
    </style>
  <link rel="stylesheet" href="{{asset('asset/css/frontend/cart/slick-theme.min.css')}}" integrity="sha512-17EgCFERpgZKcm0j0fEq1YCJuyAWdz9KUtv1EjVuaOz8pDnh/0nZxmU6BBXwaaxqoi9PQXnRWqlcDB027hgv9A==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="{{asset('asset/css/frontend/cart/slick.css')}}" rel="stylesheet" type="text/css" media="all">
    <link href="{{asset('asset/css/frontend/cart/base.css')}}" rel="stylesheet" type="text/css" media="all">
    <link href="{{asset('asset/css/frontend/cart/custom.css')}}" rel="stylesheet" type="text/css" media="all">
    <link href="{{asset('asset/css/frontend/cart/custom-overrides.css')}}" rel="stylesheet" type="text/css" media="all">
<link rel="preload" as="font" href="https://uniformers.pk/cdn/fonts/montserrat/montserrat_n4.1d581f6d4bf1a97f4cbc0b88b933bc136d38d178.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&amp;h2=dW5pZm9ybWVycy5waw&amp;hmac=56d499827e0eb4f5ccbf96eefbdba6719341e29dddae729a7b07d57db0ce9b54" type="font/woff2" crossorigin="">
<link rel="preload" as="font" href="https://uniformers.pk/cdn/fonts/montserrat/montserrat_n4.1d581f6d4bf1a97f4cbc0b88b933bc136d38d178.woff2?h1=dW5pZm9ybWVydW5pZm9ybS5hY2NvdW50Lm15c2hvcGlmeS5jb20&amp;h2=dW5pZm9ybWVycy5waw&amp;hmac=56d499827e0eb4f5ccbf96eefbdba6719341e29dddae729a7b07d57db0ce9b54" type="font/woff2" crossorigin="">
<link rel="stylesheet" href="{{asset('asset/css/frontend/cart/component-predictive-search.css')}}" media="all" onload="this.media=&#39;all&#39;">
<link href="{{asset('asset/css/frontend/cart/css2')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('asset/css/frontend/cart/font-awesome.min.css')}}" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    
<!--AZEXO_Header_Scripts-->
 <script>
        var azh = {
            shop: 'uniformers.pk',
            device_prefixes: {"lg":{"label":"Large device","width":false,"height":false,"container":1170,"min":1200},"md":{"label":"Medium device","width":992,"height":false,"container":970,"max":1199,"min":992},"sm":{"label":"Small device","width":768,"height":1150,"container":750,"max":991,"min":768},"xs":{"label":"Extra small device","width":320,"height":750,"max":767}},
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
          moneyFormat: "Rs.{{123}}"
        }
        </script><style>.az-container {
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
    <script src="{{asset('asset/js/frontend/jquery.min.js.download')}}"></script>
    <script src="{{asset('asset/js/frontend/sweetalert.min.js.download')}}"></script>
    <script>document.documentElement.className = document.documentElement.className.replace('no-js', 'js');
    if (Shopify.designMode) {
      document.documentElement.classList.add('shopify-design-mode');
    }
    </script>
  <link href="https://monorail-edge.shopifysvc.com/" rel="dns-prefetch">
<script>(function(){if ("sendBeacon" in navigator && "performance" in window) {var session_token = document.cookie.match(/_shopify_s=([^;]*)/);function handle_abandonment_event(e) {var entries = performance.getEntries().filter(function(entry) {return /monorail-edge.shopifysvc.com/.test(entry.name);});if (!window.abandonment_tracked && entries.length === 0) {window.abandonment_tracked = true;var currentMs = Date.now();var navigation_start = performance.timing.navigationStart;var payload = {shop_id: 44344246432,url: window.location.href,navigation_start,duration: currentMs - navigation_start,session_token: session_token && session_token.length === 2 ? session_token[1] : "",page_type: "cart"};window.navigator.sendBeacon("https://monorail-edge.shopifysvc.com/v1/produce", JSON.stringify({schema_id: "online_store_buyer_site_abandonment/1.1",payload: payload,metadata: {event_created_at_ms: currentMs,event_sent_at_ms: currentMs}}));}}window.addEventListener('pagehide', handle_abandonment_event);}}());</script>
<script id="web-pixels-manager-setup">(function d(d,e,r,a,n){var o,i,t,s,l=(i=(o={modern:/Edge?\/(1{2}[4-9]|1[2-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Firefox\/(1{2}[4-9]|1[2-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Chrom(ium|e)\/(9{2}|\d{3,})\.\d+(\.\d+|)|(Maci|X1{2}).+ Version\/(15\.\d+|(1[6-9]|[2-9]\d|\d{3,})\.\d+)([,.]\d+|)( \(\w+\)|)( Mobile\/\w+|) Safari\/|Chrome.+OPR\/(9{2}|\d{3,})\.\d+\.\d+|(CPU[ +]OS|iPhone[ +]OS|CPU[ +]iPhone|CPU IPhone OS|CPU iPad OS)[ +]+(15[._]\d+|(1[6-9]|[2-9]\d|\d{3,})[._]\d+)([._]\d+|)|Android:?[ /-](12[89]|1[3-9]\d|[2-9]\d{2}|\d{4,})(\.\d+|)(\.\d+|)|Android.+Firefox\/(12[7-9]|1[3-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+Chrom(ium|e)\/(12[89]|1[3-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|SamsungBrowser\/([2-9]\d|\d{3,})\.\d+/,legacy:/Edge?\/(1[6-9]|[2-9]\d|\d{3,})\.\d+(\.\d+|)|Firefox\/(5[4-9]|[6-9]\d|\d{3,})\.\d+(\.\d+|)|Chrom(ium|e)\/(5[1-9]|[6-9]\d|\d{3,})\.\d+(\.\d+|)([\d.]+$|.*Safari\/(?![\d.]+ Edge\/[\d.]+$))|(Maci|X1{2}).+ Version\/(10\.\d+|(1[1-9]|[2-9]\d|\d{3,})\.\d+)([,.]\d+|)( \(\w+\)|)( Mobile\/\w+|) Safari\/|Chrome.+OPR\/(3[89]|[4-9]\d|\d{3,})\.\d+\.\d+|(CPU[ +]OS|iPhone[ +]OS|CPU[ +]iPhone|CPU IPhone OS|CPU iPad OS)[ +]+(10[._]\d+|(1[1-9]|[2-9]\d|\d{3,})[._]\d+)([._]\d+|)|Android:?[ /-](12[89]|1[3-9]\d|[2-9]\d{2}|\d{4,})(\.\d+|)(\.\d+|)|Mobile Safari.+OPR\/([89]\d|\d{3,})\.\d+\.\d+|Android.+Firefox\/(12[7-9]|1[3-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+Chrom(ium|e)\/(12[89]|1[3-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+(UC? ?Browser|UCWEB|U3)[ /]?(15\.([5-9]|\d{2,})|(1[6-9]|[2-9]\d|\d{3,})\.\d+)\.\d+|SamsungBrowser\/(5\.\d+|([6-9]|\d{2,})\.\d+)|Android.+MQ{2}Browser\/(14(\.(9|\d{2,})|)|(1[5-9]|[2-9]\d|\d{3,})(\.\d+|))(\.\d+|)|K[Aa][Ii]OS\/(3\.\d+|([4-9]|\d{2,})\.\d+)(\.\d+|)/}).modern,t=o.legacy,s=navigator.userAgent,i.test(s)?"modern":(t.test(s),"legacy"));window.Shopify=window.Shopify||{};var c=window.Shopify;c.analytics=c.analytics||{};var u=c.analytics;u.replayQueue=[],u.publish=function(d,e,r){return u.replayQueue.push([d,e,r]),!0};try{self.performance.mark("wpm:start")}catch(d){}var h=[r,"/wpm","/b",n,l.substring(0,1),".js"].join("");!function(d){var e=d.src,r=d.async,a=void 0===r||r,n=d.onload,o=d.onerror,i=document.createElement("script"),t=document.head,s=document.body;i.async=a,i.src=e,n&&i.addEventListener("load",n),o&&i.addEventListener("error",o),t?t.appendChild(i):s?s.appendChild(i):console.error("Did not find a head or body element to append the script")}({src:h,async:!0,onload:function(){var r=window.webPixelsManager.init(d);e(r);var a=window.Shopify.analytics;a.replayQueue.forEach((function(d){var e=d[0],a=d[1],n=d[2];r.publishCustomEvent(e,a,n)})),a.replayQueue=[],a.publish=r.publishCustomEvent,a.visitor=r.visitor},onerror:function(){var e=d.storefrontBaseUrl.replace(/\/$/,""),r="".concat(e,"/.well-known/shopify/monorail/unstable/produce_batch"),n=JSON.stringify({metadata:{event_sent_at_ms:(new Date).getTime()},events:[{schema_id:"web_pixels_manager_load/3.1",payload:{version:a||"latest",bundle_target:l,page_url:self.location.href,status:"failed",surface:d.surface,error_msg:"".concat(h," has failed to load")},metadata:{event_created_at_ms:(new Date).getTime()}}]});try{if(self.navigator.sendBeacon.bind(self.navigator)(r,n))return!0}catch(d){}var o=new XMLHttpRequest;try{return o.open("POST",r,!0),o.setRequestHeader("Content-Type","text/plain"),o.send(n),!0}catch(d){console&&console.warn&&console.warn("[Web Pixels Manager] Got an unhandled error while logging a load error.")}return!1}})})({shopId: 44344246432,storefrontBaseUrl: "https://uniformers.pk",extensionsBaseUrl: "https://extensions.shopifycdn.com/cdn/shopifycloud/web-pixels-manager",surface: "storefront-renderer",enabledBetaFlags: ["6a396365"],webPixelsConfigList: [{"id":"563413152","configuration":"{\"config\":\"{\\\"pixel_id\\\":\\\"G-DLCPRM0H5Z\\\",\\\"target_country\\\":\\\"PK\\\",\\\"gtag_events\\\":[{\\\"type\\\":\\\"begin_checkout\\\",\\\"action_label\\\":\\\"G-DLCPRM0H5Z\\\"},{\\\"type\\\":\\\"search\\\",\\\"action_label\\\":\\\"G-DLCPRM0H5Z\\\"},{\\\"type\\\":\\\"view_item\\\",\\\"action_label\\\":[\\\"G-DLCPRM0H5Z\\\",\\\"MC-VQG8WXWEHH\\\"]},{\\\"type\\\":\\\"purchase\\\",\\\"action_label\\\":[\\\"G-DLCPRM0H5Z\\\",\\\"MC-VQG8WXWEHH\\\"]},{\\\"type\\\":\\\"page_view\\\",\\\"action_label\\\":[\\\"G-DLCPRM0H5Z\\\",\\\"MC-VQG8WXWEHH\\\"]},{\\\"type\\\":\\\"add_payment_info\\\",\\\"action_label\\\":\\\"G-DLCPRM0H5Z\\\"},{\\\"type\\\":\\\"add_to_cart\\\",\\\"action_label\\\":\\\"G-DLCPRM0H5Z\\\"}],\\\"enable_monitoring_mode\\\":false}\"}","eventPayloadVersion":"v1","runtimeContext":"OPEN","scriptVersion":"642ed32d7fafb3e0986c8ba3bf3908a1","type":"APP","apiClientId":1780363,"privacyPurposes":[]},{"id":"267223200","configuration":"{\"pixel_id\":\"993257121211378\",\"pixel_type\":\"facebook_pixel\",\"metaapp_system_user_token\":\"-\"}","eventPayloadVersion":"v1","runtimeContext":"OPEN","scriptVersion":"8d894c63179843e74a9691414b5ad83d","type":"APP","apiClientId":2329312,"privacyPurposes":["ANALYTICS","MARKETING","SALE_OF_DATA"]},{"id":"shopify-app-pixel","configuration":"{}","eventPayloadVersion":"v1","runtimeContext":"STRICT","scriptVersion":"0220","apiClientId":"shopify-pixel","type":"APP","privacyPurposes":["ANALYTICS","MARKETING"]},{"id":"shopify-custom-pixel","eventPayloadVersion":"v1","runtimeContext":"LAX","scriptVersion":"0220","apiClientId":"shopify-pixel","type":"CUSTOM","privacyPurposes":["ANALYTICS","MARKETING"]}],isMerchantRequest: false,initData: {"shop":{"name":"UNIFORMERS","paymentSettings":{"currencyCode":"PKR"},"myshopifyDomain":"uniformeruniform.myshopify.com","countryCode":"PK","storefrontUrl":"https:\/\/uniformers.pk"},"customer":null,"cart":null,"checkout":null,"productVariants":[],"purchasingCompany":null},},function pageEvents(webPixelsManagerAPI) {webPixelsManagerAPI.publish("page_viewed", {});webPixelsManagerAPI.publish("cart_viewed", {"cart":null});},"https://uniformers.pk/cdn","61fc5d08de09438dd514a8c1c3c0ed8d7e4f4914","8f4b3340w714f6111pf79f3ff6mc2383c0b",);</script><script async="" src="./Your Shopping Cart – UNIFORMERS_files/b8f4b3340w714f6111pf79f3ff6mc2383c0bm.js.download"></script>  <script>window.ShopifyAnalytics = window.ShopifyAnalytics || {};
window.ShopifyAnalytics.meta = window.ShopifyAnalytics.meta || {};
window.ShopifyAnalytics.meta.currency = 'PKR';
var meta = {"page":{"pageType":"cart"}};
for (var attr in meta) {
  window.ShopifyAnalytics.meta[attr] = meta[attr];
}</script>
<script>window.ShopifyAnalytics.merchantGoogleAnalytics = function() {
  
};
</script>
<script class="analytics">(function () {
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
        return Monorail.sendRequest("https://" + monorailDomain + "/v1/produce", JSON.stringify(event));
      },
      sendRequest: function sendRequest(endpointUrl, payload) {
        // Try the sendBeacon API
        if (window && window.navigator && typeof window.navigator.sendBeacon === 'function' && typeof window.Blob === 'function' && !Monorail.isIos12()) {
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
        return window.navigator.userAgent.lastIndexOf('iPhone; CPU iPhone OS 12_') !== -1 || window.navigator.userAgent.lastIndexOf('iPad; CPU OS 12_') !== -1;
      }
    };
    Monorail.produce('monorail-edge.shopifysvc.com',
      'trekkie_storefront_load_errors/1.1',
      {shop_id: 44344246432,
      theme_id: 136537014432,
      app_name: "storefront",
      context_url: window.location.href,
      source_url: "//uniformers.pk/cdn/s/trekkie.storefront.f713401b24dad9f39ade8f88b81d533f42427af4.min.js"});

        };
        scriptFallback.async = true;
        scriptFallback.src = '//uniformers.pk/cdn/s/trekkie.storefront.f713401b24dad9f39ade8f88b81d533f42427af4.min.js';
        first.parentNode.insertBefore(scriptFallback, first);
      };
      script.async = true;
      script.src = '//uniformers.pk/cdn/s/trekkie.storefront.f713401b24dad9f39ade8f88b81d533f42427af4.min.js';
      first.parentNode.insertBefore(script, first);
    };
    trekkie.load(
      {"Trekkie":{"appName":"storefront","development":false,"defaultAttributes":{"shopId":44344246432,"isMerchantRequest":null,"themeId":136537014432,"themeCityHash":"11120136684809572722","contentLanguage":"en","currency":"PKR"},"isServerSideCookieWritingEnabled":true,"monorailRegion":"shop_domain"},"Session Attribution":{},"S2S":{"facebookCapiEnabled":true,"source":"trekkie-storefront-renderer","apiClientId":580111}}
    );

    var loaded = false;
    trekkie.ready(function() {
      if (loaded) return;
      loaded = true;

      window.ShopifyAnalytics.lib = window.trekkie;

  
      var originalDocumentWrite = document.write;
      document.write = customDocumentWrite;
      try { window.ShopifyAnalytics.merchantGoogleAnalytics.call(this); } catch(error) {};
      document.write = originalDocumentWrite;

      window.ShopifyAnalytics.lib.page(null,{"pageType":"cart"});

      var match = window.location.pathname.match(/checkouts\/(.+)\/(thank_you|post_purchase)/)
      var token = match? match[1]: undefined;
      if (!hasLoggedConversion(token)) {
        setCookieIfConversion(token);
        
      }
    });


        var eventsListenerScript = document.createElement('script');
        eventsListenerScript.async = true;
        eventsListenerScript.src = "//uniformers.pk/cdn/shopifycloud/shopify/assets/shop_events_listener-20905db421adb60b04582abab58b285362bc2e1011d17cd3eabb3bfe05798c59.js";
        document.getElementsByTagName('head')[0].appendChild(eventsListenerScript);

})();</script><script async="" src="./Your Shopping Cart – UNIFORMERS_files/shop_events_listener-20905db421adb60b04582abab58b285362bc2e1011d17cd3eabb3bfe05798c59.js.download"></script>
<script class="boomerang">
(function () {
  window.BOOMR = window.BOOMR || {};
  window.BOOMR.themeName = "Talha Amjad DW";
  window.BOOMR.themeVersion = "9.0.0";
  window.BOOMR.shopId = 44344246432;
  window.BOOMR.themeId = 136537014432;
})();</script>
<script defer="" src="./Your Shopping Cart – UNIFORMERS_files/shopify-perf-kit-1.1.0.min.js.download" data-application="storefront-renderer" data-shop-id="44344246432" data-render-region="gcp-europe-west1" data-page-type="cart" data-theme-instance-id="136537014432" data-monorail-region="shop_domain" data-resource-timing-sampling-rate="10"></script>
<script src="./Your Shopping Cart – UNIFORMERS_files/pixel.modern.js.download" async=""></script><script src="./Your Shopping Cart – UNIFORMERS_files/pixel.modern(1).js.download" async=""></script><script type="text/javascript" src="./Your Shopping Cart – UNIFORMERS_files/consent-tracking-api.js.download"></script><link rel="dns-prefetch preconnect" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/polyfills.aaTJ3aJS.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/app.BlJPy2-m.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/page-OnePage.3xV3rG_8.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/DeliveryMethodSelectorSection.BVANgmCs.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/useUiComponentsColorContrast.B8lJkLKB.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/VaultedPayment.DpisrMKJ.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/LocalizationExtensionField.BXje4g59.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/RememberMeDescriptionText.CyFjihN7.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/PayButtonSection.BnD1sLJp.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/component-ShopPayVerificationSwitch.D0iegynF.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/useSubscribeMessenger.Nl1kue3B.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/index.Dm3crLVN.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/app.BIDrsn4T.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/OnePage.PMX4OSBO.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/DeliveryMethodSelectorSection.BlcMIyqF.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/useUiComponentsColorContrast.DCOTvxC3.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/ShopPayVerificationSwitch.DW7NMDXG.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="image" href="https://cdn.shopify.com/s/files/1/0443/4424/6432/files/gx-unfrmrs-logo_cabc190b-6151-4491-ae0f-e086c1fc23fa_x320.png?v=1689835567" crossorigin=""></head>
<script defer="" src="./Your Shopping Cart – UNIFORMERS_files/shopify-perf-kit-1.1.0.min.js.download" data-application="storefront-renderer" data-shop-id="44344246432" data-render-region="gcp-europe-west1" data-page-type="cart" data-theme-instance-id="136537014432" data-monorail-region="shop_domain" data-resource-timing-sampling-rate="10"></script>
<script src="./Your Shopping Cart – UNIFORMERS_files/pixel.modern.js.download" async=""></script><script src="./Your Shopping Cart – UNIFORMERS_files/pixel.modern(1).js.download" async=""></script><script type="text/javascript" src="./Your Shopping Cart – UNIFORMERS_files/consent-tracking-api.js.download"></script><link rel="dns-prefetch preconnect" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/polyfills.aaTJ3aJS.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/app.BlJPy2-m.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/page-OnePage.3xV3rG_8.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/DeliveryMethodSelectorSection.BVANgmCs.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/useUiComponentsColorContrast.B8lJkLKB.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/VaultedPayment.DpisrMKJ.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/LocalizationExtensionField.BXje4g59.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/RememberMeDescriptionText.CyFjihN7.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/PayButtonSection.BnD1sLJp.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/component-ShopPayVerificationSwitch.D0iegynF.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/useSubscribeMessenger.Nl1kue3B.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="script" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/index.Dm3crLVN.js" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/app.BIDrsn4T.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/OnePage.PMX4OSBO.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/DeliveryMethodSelectorSection.BlcMIyqF.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/useUiComponentsColorContrast.DCOTvxC3.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="style" href="https://cdn.shopify.com/shopifycloud/checkout-web/assets/c1.en/assets/ShopPayVerificationSwitch.DW7NMDXG.css" crossorigin=""><link rel="prefetch" fetchpriority="low" as="image" href="https://cdn.shopify.com/s/files/1/0443/4424/6432/files/gx-unfrmrs-logo_cabc190b-6151-4491-ae0f-e086c1fc23fa_x320.png?v=1689835567" crossorigin=""></head>

  <body class="gradient cart cart- vsc-initialized" cz-shortcut-listen="true">
    <a class="skip-to-content-link button visually-hidden" href="https://uniformers.pk/cart#MainContent">
      Skip to content
    </a><!-- BEGIN sections: header-group -->
  <div id="shopify-section-sections--16988182118560__announcement-bar" class="shopify-section shopify-section-group-header-group announcement-bar-section">
    <style>
      .announcement-bar {
      overflow: hidden;
      display: flex;
    }
    .announcement-bar__message {
        min-width: fit-content;
    }

    .announcement-bar__message {
      padding: 11px 20px;
      font-family: Lato;
      font-size: 13px;
      font-weight: 500;
      font-stretch: normal;
      font-style: normal;
      line-height: 1.21!important;
      letter-spacing: normal;
      text-align: center;
      white-space: nowrap;
    }
    @media (min-width:750px) {
      .announcement-bar__message {
        min-width: 1200px;
        font-size: 17px;
      }
    }
  </style>
  <style>
          #shopify-section-sections--16988182118560__announcement-bar .gradient {color: var(--gradient-base-accent-2); background: rgb(var(--color-foreground));} #shopify-section-sections--16988182118560__announcement-bar .gradient * {color: inherit;} 
  </style>
  </div>
<div id="shopify-section-sections--16988182118560__header" class="shopify-section shopify-section-group-header-group section-header shopify-section-header-sticky">
<link rel="stylesheet" href="{{asset('asset/css/frontend/component-list-menu.css')}}" media="all" onload="this.media=&#39;all&#39;">
<link rel="stylesheet" href="{{asset('asset/css/frontend/component-search.css')}}" media="all" onload="this.media=&#39;all&#39;">
<link rel="stylesheet" href="{{asset('asset/css/frontend/component-menu-drawer.css')}}" media="all" onload="this.media=&#39;all&#39;">
<link rel="stylesheet" href="{{asset('asset/css/frontend/component-cart-notification.css')}}" media="all" onload="this.media=&#39;all&#39;">
<link rel="stylesheet" href="{{asset('asset/css/frontend/component-cart-items.css')}}" media="all" onload="this.media=&#39;all&#39;">
<link rel="stylesheet" href="{{asset('asset/css/frontend/component-price.css')}}" media="all" onload="this.media=&#39;all&#39;">
<link rel="stylesheet" href="{{asset('asset/css/frontend/component-loading-overlay.css')}}" media="all" onload="this.media=&#39;all&#39;">
<link href="{{asset('asset/css/frontend/component-slider.css')}}" rel="stylesheet" type="text/css" media="all">

<noscript><link href="//uniformers.pk/cdn/shop/t/22/assets/component-list-menu.css?v=151968516119678728991721599981" rel="stylesheet" type="text/css" media="all" /></noscript>
<noscript><link href="//uniformers.pk/cdn/shop/t/22/assets/component-search.css?v=184225813856820874251721599981" rel="stylesheet" type="text/css" media="all" /></noscript>
<noscript><link href="//uniformers.pk/cdn/shop/t/22/assets/component-menu-drawer.css?v=48704890152900627761721599981" rel="stylesheet" type="text/css" media="all" /></noscript>
<noscript><link href="//uniformers.pk/cdn/shop/t/22/assets/component-cart-notification.css?v=108833082844665799571721599981" rel="stylesheet" type="text/css" media="all" /></noscript>
<noscript><link href="//uniformers.pk/cdn/shop/t/22/assets/component-cart-items.css?v=29412722223528841861721599981" rel="stylesheet" type="text/css" media="all" /></noscript>

<style>
  header-drawer {
    justify-self: start;
    margin-left: -1.2rem;
  }.menu-drawer-container {
    display: flex;
  }

  .list-menu {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .list-menu--inline {
    display: inline-flex;
    flex-wrap: wrap;
  }

  summary.list-menu__item {
    padding-right: 2.7rem;
  }

  .list-menu__item {
    display: flex;
    align-items: center;
    line-height: calc(1 + 0.3 / var(--font-body-scale));
  }

  .list-menu__item--link {
    text-decoration: none;
    padding-bottom: 1rem;
    padding-top: 1rem;
    line-height: calc(1 + 0.8 / var(--font-body-scale));
  }

  @media screen and (min-width: 750px) {
    .list-menu__item--link {
      padding-bottom: 0.5rem;
      padding-top: 0.5rem;
    }
  }
</style><style data-shopify="">.header {
    padding-top: 2px;
    padding-bottom: 0px;
  }

  .section-header {
    position: sticky; /* This is for fixing a Safari z-index issue. PR #2147 */
    margin-bottom: 0px;
  }

  @media screen and (min-width: 750px) {
    .section-header {
      margin-bottom: 0px;
    }
  }

  @media screen and (min-width: 990px) {
    .header {
      padding-top: 4px;
      padding-bottom: 0px;
    }
  }</style>
<script src="{{asset('asset/js/frontend/details-disclosure.js.download')}}" defer="defer"></script>
<script src="{{asset('asset/js/frontend/details-modal.js.download')}}" defer="defer"></script>
<script src="{{asset('asset/js/frontend/details-disclosure.js.download/cart-notification.js.download')}}" defer="defer"></script>
<script src="{{asset('asset/js/frontend/details-disclosure.js.download/search-form.js.download')}}" defer="defer"></script><svg xmlns="http://www.w3.org/2000/svg" class="hidden">
   
 

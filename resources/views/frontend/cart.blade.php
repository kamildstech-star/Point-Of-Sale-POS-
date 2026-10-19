@extends('layouts.frontend_master')
@section("meta")
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="">
    <meta property="og:site_name" content="UNIFORMERS">
    <meta property="og:url" content="https://uniformers.pk/cart">
    <meta property="og:title" content="Your Shopping Cart">
    <meta property="og:type" content="website">
    <meta property="og:description" content="UNIFORMERS">
    <meta property="og:image" content="http://uniformers.pk/cdn/shop/files/IMG_1200x1200.jpg?v=1639470029">
    <meta property="og:image:secure_url" content="https://uniformers.pk/cdn/shop/files/IMG_1200x1200.jpg?v=1639470029">
    <meta name="twitter:site" content="@UNIFORMERS1">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Your Shopping Cart">
    <meta name="twitter:description" content="UNIFORMERS">
    <link rel="canonical" href="https://uniformers.pk/cart">
    <link rel="preconnect" href="https://cdn.shopify.com/" crossorigin=""><link rel="icon" type="image/png" href="https://uniformers.pk/cdn/shop/files/Favicon-navy_1.png?crop=center&amp;height=32&amp;v=1615321855&amp;width=32">
    <link rel="preconnect" href="https://fonts.shopifycdn.com/" crossorigin=""><title>
          Your Shopping Cart– BEST UNIFORM</title>
    <meta property="og:site_name" content="UNIFORMERS">
    <meta property="og:url" content="https://uniformers.pk/cart">
    <meta property="og:title" content="Your Shopping Cart">
    <meta property="og:type" content="website">
    <meta property="og:description" content="UNIFORMERS">
    <meta property="og:image" content="http://uniformers.pk/cdn/shop/files/IMG.jpg?v=1639470029">
    <meta property="og:image:secure_url" content="https://uniformers.pk/cdn/shop/files/IMG.jpg?v=1639470029">
    <meta property="og:image:width" content="793">
    <meta property="og:image:height" content="851">
    <meta name="twitter:site" content="@UNIFORMERS1">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Your Shopping Cart">
    <meta name="twitter:description" content="UNIFORMERS">
    <meta name="google-site-verification" content="z_8voOeg-l_m3Kd95HlKL_LinmsEFHScOnChzlLqAD0">
    <meta id="shopify-digital-wallet" name="shopify-digital-wallet" content="/44344246432/digital_wallets/dialog">
    @include("frontend.cart-css")
@endsection
@section('content')
    <main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
      <div id="shopify-section-template--16988185723040__cart-items" class="shopify-section"><link href="{{asset('asset/css/frontend/cart/component-cart.css')}}" rel="stylesheet" type="text/css" media="all">
<link href="{{asset('asset/css/frontend/cart/component-cart-items.css')}}" rel="stylesheet" type="text/css" media="all">
<link href="{{asset('asset/css/frontend/cart/component-totals.css')}}" rel="stylesheet" type="text/css" media="all">
<link href="{{asset('asset/css/frontend/cart/component-price.css')}}" rel="stylesheet" type="text/css" media="all">
<link href="{{asset('asset/css/frontend/cart/component-discounts.css')}}" rel="stylesheet" type="text/css" media="all">
<link href="{{asset('asset/css/frontend/cart/component-loading-overlay.css')}}" rel="stylesheet" type="text/css" media="all">
<style data-shopify="">.section-template--16988185723040__cart-items-padding {
    padding-top: 27px;
    padding-bottom: 27px;
  }

  @media screen and (min-width: 750px) {
    .section-template--16988185723040__cart-items-padding {
      padding-top: 36px;
      padding-bottom: 36px;
    }
  }</style><script src="{{asset('asset/css/frontend/cart/cart.js.download')}}" defer="defer"></script><cart-items class="page-width is-empty">
  <div class="title-wrapper-with-link">
    <h1 class="title title--primary">Your cart</h1>
    <a href="https://uniformers.pk/collections/all" class="underlined-link">Continue shopping</a>
  </div>

  <div class="cart__warnings">
    <h1 class="cart__empty-text">Your cart is empty</h1>
    <a href="{{route('frontend.allProducts')}}" class="button">
      Continue shopping
    </a><h2 class="cart__login-title">Have an account?</h2>
      <p class="cart__login-paragraph">
        <a href="{{route('frontend-login')}}" class="link underlined-link">Log in</a> to check out faster.
      </p></div>

  <form action="https://uniformers.pk/cart" class="cart__contents critical-hidden" method="post" id="cart">
    <div class="cart__items" id="main-cart-items" data-id="template--16988185723040__cart-items">
      <div class="js-contents"></div>
    </div>

    <p class="visually-hidden" id="cart-live-region-text" aria-live="polite" role="status"></p>
    <p class="visually-hidden" id="shopping-cart-line-item-status" aria-live="polite" aria-hidden="true" role="status">
      Loading...
    </p>
  </form>
</cart-items>


</div><div id="shopify-section-template--16988185723040__cart-footer" class="shopify-section cart__footer-wrapper"><link href="{{asset('asset/css/frontend/cart/component-cart.css')}}" rel="stylesheet" type="text/css" media="all">
<link href="{{asset('asset/css/frontend/cart/component-totals.css')}}" rel="stylesheet" type="text/css" media="all">
<link href="{{asset('asset/css/frontend/cart/component-price.css')}}" rel="stylesheet" type="text/css" media="all">
<link href="{{asset('asset/css/frontend/cart/component-discounts.css')}}" rel="stylesheet" type="text/css" media="all">

<div class="page-width is-empty" id="main-cart-footer" data-id="template--16988185723040__cart-footer">
  <div>
    <div class="cart__footer"><div class="cart__blocks">
        
<div class="js-contents">
                <div class="totals">
                  <h2 class="totals__subtotal">Subtotal</h2>
                  <p class="totals__subtotal-value">Rs.0.00 PKR</p>
                </div>

                <div></div>

                
              </div>
<div class="cart__ctas">
                <noscript>
                  <button type="submit" class="cart__update-button button button--secondary" form="cart">
                    Update
                  </button>
                </noscript>

                <button type="submit" id="checkout" class="cart__checkout-button button" name="checkout" disabled="" form="cart">
                  Check out
                </button>
              </div>

        <div id="cart-errors"></div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    function isIE() {
      const ua = window.navigator.userAgent;
      const msie = ua.indexOf('MSIE ');
      const trident = ua.indexOf('Trident/');

      return msie > 0 || trident > 0;
    }

    if (!isIE()) return;
    const cartSubmitInput = document.createElement('input');
    cartSubmitInput.setAttribute('name', 'checkout');
    cartSubmitInput.setAttribute('type', 'hidden');
    document.querySelector('#cart').appendChild(cartSubmitInput);
    document.querySelector('#checkout').addEventListener('click', function (event) {
      document.querySelector('#cart').submit();
    });
  });
</script>


</div>
    </main>
@endsection




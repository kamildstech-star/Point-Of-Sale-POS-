@extends('layouts.frontend_master')
@section('meta')
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="">
    <meta property="og:site_name" content="UNIFORMERS">
    <meta property="og:url" content="https://uniformers.pk/account">
    <meta property="og:title" content="Account">
    <meta property="og:type" content="website">
    <meta property="og:description" content="UNIFORMERS">
    <meta property="og:image" content="http://uniformers.pk/cdn/shop/files/IMG_1200x1200.jpg?v=1639470029">
    <meta property="og:image:secure_url" content="https://uniformers.pk/cdn/shop/files/IMG_1200x1200.jpg?v=1639470029">
    <meta name="twitter:site" content="@UNIFORMERS1">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Account">
    <meta name="twitter:description" content="UNIFORMERS">
    <link rel="canonical" href="https://uniformers.pk/account">
    <link rel="preconnect" href="https://cdn.shopify.com/" crossorigin=""><link rel="icon" type="image/png" href="https://uniformers.pk/cdn/shop/files/Favicon-navy_1.png?crop=center&amp;height=32&amp;v=1615321855&amp;width=32"><link rel="preconnect" href="https://fonts.shopifycdn.com/" crossorigin="">
    <title>Account– BEST UNIFORMRS</title>
    <meta property="og:site_name" content="UNIFORMERS">
    <meta property="og:url" content="https://uniformers.pk/account">
    <meta property="og:title" content="Account">
    <meta property="og:type" content="website">
    <meta property="og:description" content="UNIFORMERS"><meta property="og:image" content="http://uniformers.pk/cdn/shop/files/IMG.jpg?v=1639470029">
    <meta property="og:image:secure_url" content="https://uniformers.pk/cdn/shop/files/IMG.jpg?v=1639470029">
    <meta property="og:image:width" content="793">
    <meta property="og:image:height" content="851"><meta name="twitter:site" content="@UNIFORMERS1"><meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Account">
    <meta name="twitter:description" content="UNIFORMERS">
    @include('frontend.account-css')
@endsection
@section('content')
<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
    <div id="shopify-section-template--16988185788576__main" class="shopify-section">
        <link href="{{asset('asset/css/frontend/customer.css')}}" rel="stylesheet" type="text/css" media="all">
    <style data-shopify="">.section-template--16988185788576__main-padding {
  padding-top: 27px;
  padding-bottom: 27px;
}

@media screen and (min-width: 750px) {
  .section-template--16988185788576__main-padding {
    padding-top: 36px;
    padding-bottom: 36px;
  }
}</style><div class="customer account section-template--16988185788576__main-padding">
<div>
  <h1 class="customer__title">Account</h1>
  <a href="https://uniformers.pk/account/logout">
    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" fill="none" viewBox="0 0 18 19">
      <path fill-rule="evenodd" clip-rule="evenodd" d="M6 4.5a3 3 0 116 0 3 3 0 01-6 0zm3-4a4 4 0 100 8 4 4 0 000-8zm5.58 12.15c1.12.82 1.83 2.24 1.91 4.85H1.51c.08-2.6.79-4.03 1.9-4.85C4.66 11.75 6.5 11.5 9 11.5s4.35.26 5.58 1.15zM9 10.5c-2.5 0-4.65.24-6.17 1.35C1.27 12.98.5 14.93.5 18v.5h17V18c0-3.07-.77-5.02-2.33-6.15-1.52-1.1-3.67-1.35-6.17-1.35z" fill="currentColor">
    </path></svg>
    Log out
  </a>
</div>

<div>
  <div>
    <h2>Order history</h2>

    
<p>You haven't placed any orders yet.</p>
  </div>

  <div>
    <h2>Account details</h2>

    <p>Kamil Shehzad<br>AL-Najaf Colony, Street#08, P-595 BLOCK C ,Faisalabad<br>FAISALABAD 38870<br>Pakistan</p>

    <a href="https://uniformers.pk/account/addresses">
      View addresses (1)
    </a>
  </div>
</div>
</div>
</div>
  </main>

@endsection



@extends('layouts.frontend_master')
@section('meta')
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="">
    <meta property="og:site_name" content="UNIFORMERS">
    <meta property="og:url" content="https://uniformers.pk/account/register">
    <meta property="og:title" content="Create Account">
    <meta property="og:type" content="website">
    <meta property="og:description" content="UNIFORMERS">
    <meta property="og:image" content="http://uniformers.pk/cdn/shop/files/IMG_1200x1200.jpg?v=1639470029">
    <meta property="og:image:secure_url" content="https://uniformers.pk/cdn/shop/files/IMG_1200x1200.jpg?v=1639470029">
    <meta name="twitter:site" content="@UNIFORMERS1">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Create Account">
    <meta name="twitter:description" content="UNIFORMERS">
    <link rel="canonical" href="https://uniformers.pk/account/register">
    <link rel="preconnect" href="https://cdn.shopify.com" crossorigin>
    <link rel="icon" type="image/png" href="favicons/files-Favicon-navy_1.png"><link rel="preconnect" href="https://fonts.shopifycdn.com" crossorigin>
    <title>Create Account &ndash; UNIFORMERS</title>
    <meta property="og:site_name" content="UNIFORMERS">
    <meta property="og:url" content="https://uniformers.pk/account/register">
    <meta property="og:title" content="Create Account">
    <meta property="og:type" content="website">
    <meta property="og:description" content="UNIFORMERS"><meta property="og:image" content="http://uniformers.pk/cdn/shop/files/IMG.jpg?v=1639470029">
    <meta property="og:image:secure_url" content="https://uniformers.pk/cdn/shop/files/IMG.jpg?v=1639470029">
    <meta property="og:image:width" content="793">
    <meta property="og:image:height" content="851"><meta name="twitter:site" content="@UNIFORMERS1"><meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Create Account">
    <meta name="twitter:description" content="UNIFORMERS">
    @include('frontend.create-account-css')
@endsection
@section('content')
    <main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
            <div id="shopify-section-template--16988185952416__main" class="shopify-section">
            <link href="{{asset('asset/css/frontend/assets-customer.css')}}" rel="stylesheet" type="text/css" media="all">
        <style data-shopify>.section-template--16988185952416__main-padding {
        padding-top: 27px;
        padding-bottom: 27px;
        }

        @media screen and (min-width: 750px) {
        .section-template--16988185952416__main-padding {
            padding-top: 36px;
            padding-bottom: 36px;
        }
        }</style><div class="customer register section-template--16988185952416__main-padding">
        <svg style="display: none">
        <symbol id="icon-error" viewbox="0 0 13 13">
            <circle cx="6.5" cy="6.50049" r="5.5" stroke="white" stroke-width="2"></circle>
            <circle cx="6.5" cy="6.5" r="5.5" fill="#EB001B" stroke="#EB001B" stroke-width="0.7"></circle>
            <path d="M5.87413 3.52832L5.97439 7.57216H7.02713L7.12739 3.52832H5.87413ZM6.50076 9.66091C6.88091 9.66091 7.18169 9.37267 7.18169 9.00504C7.18169 8.63742 6.88091 8.34917 6.50076 8.34917C6.12061 8.34917 5.81982 8.63742 5.81982 9.00504C5.81982 9.37267 6.12061 9.66091 6.50076 9.66091Z" fill="white"></path>
            <path d="M5.87413 3.17832H5.51535L5.52424 3.537L5.6245 7.58083L5.63296 7.92216H5.97439H7.02713H7.36856L7.37702 7.58083L7.47728 3.537L7.48617 3.17832H7.12739H5.87413ZM6.50076 10.0109C7.06121 10.0109 7.5317 9.57872 7.5317 9.00504C7.5317 8.43137 7.06121 7.99918 6.50076 7.99918C5.94031 7.99918 5.46982 8.43137 5.46982 9.00504C5.46982 9.57872 5.94031 10.0109 6.50076 10.0109Z" fill="white" stroke="#EB001B" stroke-width="0.7">
        </path></symbol>
        </svg>
        <h1>
        Create account
        </h1><form method="post" action="/account" id="create_customer" accept-charset="UTF-8" data-login-with-shop-sign-up="true" novalidate="novalidate"><input type="hidden" name="form_type" value="create_customer"><input type="hidden" name="utf8" value="&#10003;"><div class="field">
            <input type="text" name="customer[first_name]" id="RegisterForm-FirstName" autocomplete="given-name" placeholder="First name">
            <label for="RegisterForm-FirstName">
            First name
            </label>
        </div>
        <div class="field">
            <input type="text" name="customer[last_name]" id="RegisterForm-LastName" autocomplete="family-name" placeholder="Last name">
            <label for="RegisterForm-LastName">
            Last name
            </label>
        </div>
        <div class="field">
            <input type="email" name="customer[email]" id="RegisterForm-email" spellcheck="false" autocapitalize="off" autocomplete="email" aria-required="true" placeholder="Email">
            <label for="RegisterForm-email">
            Email
            </label>
        </div><div class="field">
            <input type="password" name="customer[password]" id="RegisterForm-password" aria-required="true" placeholder="Password">
            <label for="RegisterForm-password">
            Password
            </label>
        </div><button>
            Create
        </button></form></div>
        </div>
    </main>
@endsection
    
  


  
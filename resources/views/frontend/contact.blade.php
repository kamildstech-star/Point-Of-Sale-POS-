@extends('layouts.frontend_master')
@section('meta')
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="">
    <meta property="og:site_name" content="UNIFORMERS">
    <meta property="og:url" content="https://uniformers.pk/pages/contact-us">
    <meta property="og:title" content="Contact Us">
    <meta property="og:type" content="website">
    <meta property="og:description" content="03-111-666-123 | 042-35402002-3 hello@uniformers.pk | sales@uniformers.pk">
    <meta property="og:image" content="http://uniformers.pk/cdn/shop/files/IMG_1200x1200.jpg?v=1639470029">
    <meta property="og:image:secure_url" content="https://uniformers.pk/cdn/shop/files/IMG_1200x1200.jpg?v=1639470029">
    <meta name="twitter:site" content="@UNIFORMERS1">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Contact Us">
    <meta name="twitter:description" content="0320 7755990 | 03087795826 bestuniform@gmail" >
    <meta property="og:site_name" content="UNIFORMERS">
    <meta property="og:url" content="https://uniformers.pk/pages/contact-us">
    <meta property="og:title" content="Contact Us">
    <meta property="og:type" content="website">
    <meta property="og:description" content="03-111-666-123 | 042-35402002-3 hello@uniformers.pk | sales@uniformers.pk"><meta property="og:image" content="http://uniformers.pk/cdn/shop/files/IMG.jpg?v=1639470029">
    <meta property="og:image:secure_url" content="https://uniformers.pk/cdn/shop/files/IMG.jpg?v=1639470029">
    <meta property="og:image:width" content="793">
    <meta property="og:image:height" content="851"><meta name="twitter:site" content="@UNIFORMERS1"><meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Contact Us">
    <meta name="twitter:description" content="03-111-666-123 | 042-35402002-3 hello@uniformers.pk | sales@uniformers.pk">
    @include('frontend.contact-css')
@endsection
@section('content')
  <main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
      <section id="shopify-section-template--16988186149024__main" class="shopify-section section">
          <link href="{{asset('asset/css/frontend/assets-section-main-page.css')}}" rel="stylesheet" type="text/css" media="all">
  <style data-shopify>.section-template--16988186149024__main-padding {
    padding-top: 15px;
    padding-bottom: 0px;
  }

  @media screen and (min-width: 750px) {
    .section-template--16988186149024__main-padding {
      padding-top: 20px;
      padding-bottom: 0px;
    }
  }</style><div class="page-width page-width--narrow section-template--16988186149024__main-padding">
  <h1 class="main-page-title page-title h0">
    Contact Us
  </h1>
  <div class="rte">
    <a href="tel:0320 7755990" aria-describedby="a11y-external-message">
        <span style="font-size: 135%;">
          <i class="fa fa-mobile"></i>
        </span>
        <span style="font-size: 12px !important;"> 0320 7755990 | 03087795826</span>
        </a>
  <br>
  <a href="mailto:bestuniform@gmail.com" aria-describedby="a11y-external-message"> <span><i class="fa fa-envelope"></i></span> <span class="" style="font-size: 12px !important;"> bestuniform@gmail.com</span> </a>
  </div>
  </div>
  </section><section id="shopify-section-template--16988186149024__form" class="shopify-section section">
      <link href="{{asset('asset/css/frontend/assets-section-contact-form.css')}}" rel="stylesheet" type="text/css" media="all">
  <style data-shopify>.section-template--16988186149024__form-padding {
    padding-top: 27px;
    padding-bottom: 27px;
  }

  @media screen and (min-width: 750px) {
    .section-template--16988186149024__form-padding {
      padding-top: 36px;
      padding-bottom: 36px;
    }
  }</style><div class="color-background-1 gradient">
  <div class="contact page-width page-width--narrow section-template--16988186149024__form-padding"><h2 class="visually-hidden">Contact form</h2><form method="post" action="/contact#ContactForm" id="ContactForm" accept-charset="UTF-8" class="isolate"><input type="hidden" name="form_type" value="contact"><input type="hidden" name="utf8" value="&#10003;"><div class="contact__fields">
        <div class="field">
          <input class="field__input" autocomplete="name" type="text" id="ContactForm-name" name="contact[Name]" value="" placeholder="Name">
          <label class="field__label" for="ContactForm-name">Name</label>
        </div>
        <div class="field field--with-error">
          <input autocomplete="email" type="email" id="ContactForm-email" class="field__input" name="contact[email]" spellcheck="false" autocapitalize="off" value="" aria-required="true" placeholder="Email">
          <label class="field__label" for="ContactForm-email">Email
            <span aria-hidden="true">*</span></label></div>
      </div>
      <div class="field">
        <input type="tel" id="ContactForm-phone" class="field__input" autocomplete="tel" name="contact[Phone number]" pattern="[0-9\-]*" value="" placeholder="Phone number">
        <label class="field__label" for="ContactForm-phone">Phone number</label>
      </div>
      <div class="field">
        <textarea rows="10" id="ContactForm-body" class="text-area field__input" name="contact[Comment]" placeholder="Comment"></textarea>
        <label class="form__label field__label" for="ContactForm-body">Comment</label>
      </div>
      <div class="contact__button">
        <button type="submit" class="button">
          Send
        </button>
      </div></form></div>
  </div>
  </section>
  </main>
@endsection
   
 
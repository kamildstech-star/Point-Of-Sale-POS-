
@extends('layouts.app')

@section('head')
  <link href="{{ asset('asset/css/login.css') }}" rel="stylesheet">
@endsection

@section('content')
  <div class="container">
    <div class="row justify-content-center main-div">
      <div class="logo">
        <img src="{{ asset('asset/img/logo.png') }}" alt="">
      </div>
      <form action="{{ route('loginpost') }}" method="post" class="form">
        @csrf
        <div class="row mb-3">
          <label for="email">E-mail</label>
          <input id="email"  type="email" class="email-input   @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">                                
          @error('email')
            <span class="invalid-feedback" role="alert">
              <strong>{{ $message }}</strong>
            </span>
          @enderror
        </div>
        <div class="row mb-3">
          <label for="password">Password</label>
          <input id="password"  type="password" class="email-input @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">                                
          @error('password')
            <span class="invalid-feedback" role="alert">
              <strong>{{ $message }}</strong>
            </span>
          @enderror
        </div>
        <div class="col-4">
          <button type="submit" class="btn-block login-btn">Log In</button>
        </div>
      </form>
    </div>
  </div>
@endsection

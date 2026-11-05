<!DOCTYPE html>
<html class="no-js" lang="en">
    <head>
        @yield("meta")
        @yield("css")
        @include('partials.remix-icons')
    </head>
    <body class="@yield('body_class', 'gradient index index-')">
        @include('partials.frontend.header');
        @yield('content')
        @include('partials.frontend.footer');
        @yield('script')
    </body>
</html>

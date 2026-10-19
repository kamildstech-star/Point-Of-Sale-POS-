{{-- toastr cdn --}}
<script src="{{ asset('asset/jquery/jquery.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- AdminLTE App -->
<script src="{{ asset('asset/js/adminlte.js') }}"></script>
<!-- Include jQuery -->
<script src="{{ asset('asset/jquery/jquery.ajax.libs.js') }}"></script>
<!-- Include Select2 CSS -->
<!-- <link href="{{ asset('asset/css/ajax.libs.selec2.css') }}" rel="stylesheet" /> -->
<!-- Include Select2 JS -->
<script src="{{ asset('asset/jquery/jquery.ajax.libs.select2.js') }}"></script>
@yield('script')

<script>       
    toastr.options ={
        "closeButton" : true,
        "progressBar" : true
    }
    
    @if(Session::has('message'))
        toastr.success("{{ session('message') }}");
    @endif

    @if(Session::has('error'))
        toastr.error("{{ session('error') }}");
    @endif

    @if(Session::has('info'))
        toastr.info("{{ session('info') }}");
    @endif

    @if(Session::has('warning'))
            toastr.warning("{{ session('warning') }}");
    @endif

    @if(Session::has('success'))
        toastr.success("{{ session('success') }}");
    @endif
</script>
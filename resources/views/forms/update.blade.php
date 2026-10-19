@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/editRole.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Edit Role</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit Role</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card ">
                <div class="card-header" style="">
                    <h3 class="card-title mt-2">Edit Role</h3>
                </div>
                <form action="{{ route('roles.update', ['id' => $roles->id]) }}" method="POST" id="updateForm">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Role</label>
                            <input type="name" name="role" class="form-control" id="name" placeholder="Role" value="{{ $roles->role }}">
                            @error('role')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn" style="background-color: rgb(223, 197, 148);font-weight:600">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <button type="button" class="btn btn-default toastsDefaultDefault">
        Launch Default Toast
    </button>
@endsection

@section('script')
    <script>       
        @if(Session::has('message'))
            toastr.options ={
                "closeButton" : true,
                "progressBar" : true
            }
                toastr.success("{{ session('message') }}");
        @endif

        @if(Session::has('error'))
            toastr.options =
            {
                "closeButton" : true,
                "progressBar" : true
            }
                toastr.error("{{ session('error') }}");
        @endif

        @if(Session::has('info'))
            toastr.options =
            {
                "closeButton" : true,
                "progressBar" : true
            }
                toastr.info("{{ session('info') }}");
            @endif

        @if(Session::has('warning'))
            toastr.options =
            {
                "closeButton" : true,
                "progressBar" : true
            }
                toastr.warning("{{ session('warning') }}");
        @endif


        @if(Session::has('success'))
            toastr.options =
            {
                "closeButton" : true,
                "progressBar" : true
            }
                toastr.success("{{ session('success') }}");
        @endif

    </script>
@endsection
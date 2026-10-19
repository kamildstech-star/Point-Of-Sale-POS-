@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/roles.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Roles</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Roles</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card ">
                <div class="card-header" style="">
                    <h3 class="card-title">Roles</h3>
                </div>
                <form action="{{ route('role.create') }}" method="POST" id="roleform">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Role</label>
                            <input type="name" name="role" class="form-control" id="role" placeholder="Role">
                            @error('role')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn" style="background-color: rgb(223, 197, 148);font-weight:600">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection


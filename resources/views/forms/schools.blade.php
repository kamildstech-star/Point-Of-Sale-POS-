@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/school.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Schools</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Schools</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card ">
                <div class="card-header" >
                    <h3 class="card-title mt-2">Add School</h3>
                </div>
                <form action="{{ route('school.create') }}" method="POST" id="schoolform">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">School</label>
                            <input type="name" name="name" class="form-control" id="name" placeholder="School">
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>            
                        <div class="form-group">
                            <label for="name">Address</label>
                            <input type="text" name="address" class="form-control" id="address" placeholder="Address">
                            @error('address')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Percentage</label>
                            <input type="number" name="percentage" class="form-control" id="Percentage" placeholder="Percentage">
                            @error('percentage')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn" >Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

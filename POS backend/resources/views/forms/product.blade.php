@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/product.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Product</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Product</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card">
                <div class="card-header" >
                    <h3 class="card-title mt-2">Add Product</h3>
                </div>
                <form action="{{ route('product.create') }}" method="POST" id="productform">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Product</label>
                            <input type="name" name="product" class="form-control" id="name" placeholder="Product">
                            @error('product')
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

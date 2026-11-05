@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/editProduct.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Edit Product</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit Product</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card ">
                <div class="card-header" >
                    <h3 class="card-title mt-2">Edit Product</h3>
                </div>
                <form action="{{ route('product.update', ['id' => $products->id]) }}" method="POST" id="editproduct">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Product</label>
                            <input type="text" name="product" class="form-control" id="name" placeholder="Update Product" value="{{ $products->name }}">
                        </div>
                        @error('product')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn" >Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

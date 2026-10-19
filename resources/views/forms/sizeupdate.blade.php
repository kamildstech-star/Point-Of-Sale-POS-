@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/editSize.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Edit Size</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit Size</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card">
                <div class="card-header" >
                    <h3 class="card-title mt-2">Edit Sizes</h3>
                </div>
                <form action="{{ route('size.update', ['id' => $sizes->id]) }}" method="POST" id="editsize">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="product_id">Product</label>
                            <select name="product_id" id="product_id" class="form-control">
                                @foreach($products as $product)
                                <option value="{{ $product->id }}" @if($product->id == $sizes->product_id) selected @endif>{{ $product->name }}</option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Size</label>
                            <input type="text" name="size" class="form-control" id="name" placeholder="Update size" value="{{ $sizes->size }}">
                            @error('size')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn" >Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            $('#product_id').select2({
                placeholder: "Select School",
                theme: "bootstrap4", // Optional: Change the theme to match Bootstrap 4
                dropdownSearch: true // Enable searching within dropdown
            });
        })
    </script>
@endsection

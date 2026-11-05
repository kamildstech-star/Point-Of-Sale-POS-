@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/size.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Sizes</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Sizes</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card">
                <div class="card-header" >
                    <h3 class="card-title mt-2">Add Size</h3>
                </div>
                <form action="{{ route('size.create') }}" method="POST" id="sizeform">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="product_id">Product</label>
                            <select name="product_id" id="product_id" class="form-control">
                                <option value="">Select Product</option> <!-- Default option -->
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="text-danger">{{ $message }}</div>                                
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Size</label>
                            <input type="name" name="size" class="form-control" id="name" placeholder="Size">
                            @error('size')
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

@section('script')
    <script>
        $(document).ready(function(){
            $('#product_id').select2({
                placeholder: "Select Product",
                theme: "bootstrap4", // Optional: Change the theme to match Bootstrap 4
                dropdownSearch: true // Enable searching within dropdown
            });
        })
    </script>
@endsection
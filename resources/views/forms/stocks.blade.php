@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/stock.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Stocks</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Stocks</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card ">
                <div class="card-header" >
                    <h3 class="card-title mt-2">Stocks</h3>  
                </div>
                <form action="{{ route('stock.create') }}" method="POST" id="stockform">
                    @csrf
                    <div class="card-body">           
                        <div class="form-group">
                            <label for="school">School</label>
                            <select name="school" class="form-control school">
                                <option value="0" m-2>Select School</option>
                                @foreach ($schools as $school)
                                    <option value="{{$school->id}}" {{ old('school') == $school->id ? 'selected' : '' }}>
                                        {{ $school->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('school')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>                    
                        {{-- <div class="form-group">
                            <label for="product">Product</label>
                            <select name="product" class="form-control product">
                                <option value="0">Choose</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>                     
                        <div class="form-group">
                            <label for="size">Size</label>
                            <select name="size" class="form-control size">
                                <option value="0">Choose</option>
                                @foreach ($sizes as $size)
                                    <option value="{{ $size->id }}" {{ old('size') == $size->id ? 'selected' : '' }}>
                                        {{ $size->size }}
                                    </option>
                                @endforeach
                            </select>
                            @error('size')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>     --}}

                        <div class="form-group">
                            <label for="product">Product</label>
                            <select name="product" class="form-control product">
                                <option value="0">Select Product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="size">Size</label>
                            <select name="size" class="form-control size">
                                <option value="0">Select Size</option>
                            </select>
                            @error('size')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="Quantity">Quantity</label>
                            <input type="number" name="quantity" class="form-control" id="quantity" placeholder="Quantity">
                            @error('quantity')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>              
                        <div class="form-group">
                            <label for="baseprice">Base Price</label>
                            <input type="number" name="baseprice" class="form-control" id="price" placeholder="Base Price">
                            @error('baseprice')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="saleprice">Sale Price</label>
                            <input type="number" name="saleprice" class="form-control" id="Percentage" placeholder="Sale Price">
                            @error('saleprice')
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
        var sizes = {!! $sizes !!}
        $(document).ready(function(){
            $('.product').on('change', function() {
                var productId = $(this).val();
                var sizeDropdown = $('.size');
                var html = '<option>Select Size</option>';

                sizes.map(function(size, i){
                    if(size.product_id == productId)
                        html += `<option value="${size.id}">${size.size}</option>`;
                });
                sizeDropdown.html(html);
                sizeDropdown.show();
            });


            $('.school').select2({
                placeholder: "Select School",
                theme: "bootstrap4", // Optional: Change the theme to match Bootstrap 4
                dropdownSearch: true // Enable searching within dropdown
            });
            $('.product').select2({
                placeholder: "Select School",
                theme: "bootstrap4", // Optional: Change the theme to match Bootstrap 4
                dropdownSearch: true // Enable searching within dropdown
            });
            $('.size').select2({
                placeholder: "Select School",
                theme: "bootstrap4", // Optional: Change the theme to match Bootstrap 4
                dropdownSearch: true // Enable searching within dropdown
            });
        });
    </script>
@endsection

@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/editStock.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Edit Stock</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit Stock</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card ">
                <div class="card-header" >
                    <h3 class="card-title mt-2">Edit Stock</h3>
                </div>
                <form action="{{ route('stock.update', ['id' => $stocks->id]) }}" method="POST" id="editstock">                
                @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="school">School</label>
                            <select name="school" id="school" class="form-control school">
                                <option value="">Select School</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}" {{ $stocks->school_id == $school->id ? 'selected' : '' }}>
                                        {{ $school->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('school')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="product">Product</label>
                            <select name="product" id="product" class="form-control product">
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" {{ $stocks->product_id == $product->id ? 'selected' : '' }}>
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
                            <select name="size" id="size" class="form-control size">
                                @foreach ($sizes as $size)
                                    @if($stocks->product_id == $size->product_id)
                                        <option value="{{ $size->id }}" {{ $stocks->size_id == $size->id ? 'selected' : '' }}>
                                            {{ $size->size }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('size')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" name="quantity" class="form-control" id="quantity" placeholder="Enter Quantity" value="{{ $stocks->quantity }}">
                            @error('quantity')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="baseprice">Base Price</label>
                            <input type="number" name="baseprice" class="form-control" id="baseprice" placeholder="Enter Base Price" value="{{ $stocks->base_price }}">
                            @error('baseprice')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="saleprice">Sale Price</label>
                            <input type="number" name="saleprice" class="form-control" id="saleprice" placeholder="Enter Sale Price" value="{{ $stocks->sale_price }}">
                            @error('saleprice')
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
          var sizes = {!! $sizes !!}
        console.log(sizes)
        $(document).ready(function(){
            $('.product').on('change', function() {
                var productId = $(this).val();
                var sizeDropdown = $('.size');
                var html = '<option>Select size</option>';

                sizes.map(function(size, i){
                    if(size.product_id == productId)
                        html += `<option value="${size.id}">${size.size}</option>`;
                });
                sizeDropdown.html(html);
                sizeDropdown.show();
            });

       
            $('#school').select2({
                theme: "bootstrap4", 
                dropdownSearch: true 
            });
            $('.product').select2({
                theme: "bootstrap4", 
                dropdownSearch: true 
            });
            $('.size').select2({
                theme: "bootstrap4", 
                dropdownSearch: true 
            });
        });
    </script>
@endsection

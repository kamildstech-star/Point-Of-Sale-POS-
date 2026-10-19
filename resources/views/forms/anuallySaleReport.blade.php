@extends('layouts.master')
@section('title')
    Anuually Sale Report
@endsection

@section('css')
    <link href="{{ asset('asset/css/downloadsales.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Annually Sales Report</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Annually Sales Report</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="card ">
            <div class="card-header" >
                <h3 class="card-title mt-2" >Annually Sales Report</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('anuuallySales.download') }}" method="POST" id="salesreportForm">
                 @csrf   
                    <div class="row item">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="fromyear">From Year</label>
                                <input type="number" name="fromyear" class="form-control fromdate" value="{{date("Y")}}" min="1900" max="2099" step="1" style="width:165px;">

                            </div>
                            @error('fromyear')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2" >
                            <div class="form-group">
                                <label for="todate">To Year</label>
                                <input type="number" name="toyear" class="form-control todate" min="1900" value="{{date("Y")}}" max="2099" step="1" style="width:165px;">
                            </div>
                            @error('toyear')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="school">Select School</label>
                                <select name="school" class="form-control school">
                                    <option value="0">Select School</option>
                                    @foreach ($schools as $school)
                                        <option value="{{$school->id}}" {{ old('school2') == $school->id ? 'selected' : '' }}>
                                            {{ $school->name }}
                                        </option> 
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="product">Select Product</label>
                                <select name="product" class="form-control product">
                                    <option value="0">Select Product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" {{ old('product') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="size">Sizes</label>
                                <select name="size" class="form-control size" value="0">
                                    <option value="0">Select Size</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <button type="button" class="btn checkbtn">Check Sale</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="downloadWrapper">
            <a href="{{ route('anuuallySales.download') }}" class="download">
                <button type="submit" class="btn downloadbtn"  form='salesreportForm'>Download</button>
            </a>
        </div>
        <div class="card saletable">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>Sale</th>
                                </tr>
                            </thead>
                            <tbody id="sale-data">
                                
                            </tbody>
                        </table>
                    </div>
                </div>        
                <div class="card-footer ">
                    <label class="col-form-label" >Total Sale:</label>
                    <span id="total-sale">0</span>
                </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"
        integrity="sha512-MpDFIChbcXl2QgipQrt1VcPHMldRILetapBl5MPCA9Y8r7qvlwx1/Mc9hNTzY+kS5kX6PdoDq41ws1HiVNLdZA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    ></script>
    <script>
         var sizes = {!! $sizes !!}
        $(document).ready(function(){
            $('.school').select2({
                placeholder: "Select School",
                theme: "bootstrap4", 
                dropdownSearch: true 
            });
            $('.product').select2({
                placeholder: "Select Product",
                theme: "bootstrap4", 
                dropdownSearch: true 
            });
            $('.size').select2({
                placeholder: "Select Size",
                theme: "bootstrap4", 
                dropdownSearch: true 
            });
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

            $('.checkbtn').click(function () {
                $.ajax({
                    url: '{{ route('annuallySales.fetch') }}',
                    type: 'GET',
                    data: $('#salesreportForm').serialize(),
                    success: function (response) {
                        if (response.success) {
                            console.log(response);
                            
                            var annuallySales = response.annuallySales;
                            var totalSale = response.totalSale;


                            // Populate table with monthly and yearly sales
                            var tableBody = $('#sale-data');
                            tableBody.empty();

                            for (var yearMonth in annuallySales) {
                                var year = yearMonth;
                                tableBody.append('<tr><td>' + year + '</td><td>' + annuallySales[yearMonth] + '</td></tr>');
                            }

                            // Update total sale
                            $('#total-sale').text(totalSale);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(error);
                    }
                });
                $('.saletable').css('display','block')
                $('.download').css('display','block');
            });
        });
    </script>
@endsection

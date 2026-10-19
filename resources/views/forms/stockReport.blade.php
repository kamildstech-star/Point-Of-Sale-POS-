@extends('layouts.master')
@section('title')
    Stock Report
@endsection
@section('css')   
	<link href="{{ asset('asset/css/stockReport.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Stock Report</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Stock Report</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="card ">
            <div class="card-header">
                <h3 class="card-title mt-2" >Stock Report</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('stocks.download') }}" method="POST"  id="salesreportForm">
                @csrf   
                    <input type="hidden" name="fullstock" id="fullstock" value="1">
                    <input type="hidden" name="lowstock" id="lowstock" value="0">
                    <div class="row item">
                        <div class="col-md-2">
                            <div class="form-group">
                                <button type="button" class="btn checkAllStock" >Check stock</button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <button type="button" class="btn checkLowStock" >Check Low stock</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="downloadWrapper">
            <button type="submit" class="btn downloadbtn"  form='salesreportForm'>Download</button>
        </div>
        <div class="card card-primary saletable mt-3 d-none">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>School</th>
                                <th>Product</th>
                                <th>Size</th>
                                <th>Quantity</th>
                            </tr>
                            </thead>
                            <tbody id="stock-data">
                              
                            </tbody>
                        </table>
                </div>
            </div>        
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            function displayStockReport(data) {
                var tableBody = $('#stock-data');
                tableBody.empty(); // Clear previous data

                $.each(data, function(index, stock) {
                    var row = $('<tr>');
                    row.append($('<td>').text(stock.school));
                    row.append($('<td>').text(stock.product));
                    row.append($('<td>').text(stock.size));
                    row.append($('<td>').text(stock.quantity));
                    tableBody.append(row);
                });

                $('.saletable').removeClass('d-none'); // Show the table
            }
            function fetchStockReport(url) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            displayStockReport(response.data); // Call function to display data
                        } else {
                            console.error('Failed to fetch stock report');
                        }
                    },
                    error: function(xhr, status, error) {
                            console.error('AJAX request failed');
                        }
                });
            }
            $('.checkAllStock').click(function() {
                fetchStockReport('{{ route('stocks.report.index') }}');
                $('#fullstock').val('1');
                $('#lowstock').val('0');
                $('.downloadbtn').show();
            });
            $('.checkLowStock').click(function() {
                fetchStockReport('{{ route('stocks.report.index') }}?low_stock=1');
                $('#fullstock').val('0');
                $('#lowstock').val('1');
                $('.downloadbtn').show(); 
            });
        })
    </script>
@endsection
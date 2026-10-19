@extends('layouts.master')

@section('title')   
Best Uniform | View Order
@endsection
@section('css')   
    <link href="{{ asset('asset/css/orderView.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">View Order</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">View Order</li>
                    </ol>
                </div>
                         
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card card-primary">
            <div class="card-header" style="background-color: #1d1d50">
                <h3 class="card-title mt-2">View Order</h3>
            </div>
            <form method="POST" id="order">
                @csrf <!-- Include CSRF token for security -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="school">School</label>
                                <input type="text" class="form-control" value="{{ $order->school }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4 ml-md-auto">
                            <div class="form-group">
                                <label for="name">Customer Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $order->name }}"readonly>
                            </div>
                        </div>
                        <div class="col-md-4 ">
                            <div class="form-group">
                                <label for="name">Phone</label>
                                <input type="tel" name="phone" class="form-control phone" value="{{ $order->phone }}" placeholder="Phone" id="phone" readonly>
                            </div>
                        </div>
                    </div>
            
                    <div class="copy mt-5">
                        @foreach($orderItems as $item) <!-- Iterate through each order item -->
                            <div class="copy-stock-row form-group" style="border-bottom: 1px solid #ced4da">
                                <div class="row item default">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="school">School</label>
                                            <input type="text" name="school" class="form-control" value="{{ $item->school->name }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="product">Product</label>
                                            <input type="text" class="form-control" value="{{ $item->product->name }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label for="size">Sizes</label>
                                            <input type="text" class="form-control" value="{{ $item->size->size }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="price">Price</label>
                                            <input type="number" class="form-control price" style="background-color: #fffff" value="{{ $item->price + ($item->school_margin/$item->quantity) }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="quantity">Quantity</label>
                                            <input type="number" class="form-control quantity" value="{{ $item->quantity }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="quantity">Total Price</label>
                                            <input type="number" class="form-control totalprice" value="{{ $item->total }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label for="Returned">Returned</label>
                                            <input type="number" class="form-control returend" value="{{ $item->returned }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="row mt-5">
                        <div class="col-md-3 mr-3 ">
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="subtotal" class="font-weight-bold">Sub Total : </label>
                                <span class="showprices" name="showprice">{{ $order->subtotal }}</span> 
                            </div>
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="discount" class="font-weight-bold">Discount : </label>
                                <span class="showdiscounts">{{ $order->discount }}</span>
                            </div>
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="total" class="font-weight-bold">Total : </label>
                                <span class="showtotalpricesss">{{ $order->total }}</span>
                            </div>
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="" class="font-weight-bold">Received : </label>
                                <span class="receivedAmount">{{ $order->received_amount }}</span>
                            </div>
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="total" class="font-weight-bold">Remaing : </label>
                                <span class="remaing">{{ $order->remaining_amount }}</span>
                            </div>
                        </div>
                        <div class="col-md-3 ml-md-auto ">
                            <div class="form-group col-10">
                                <label for="receieved_amount">Received Amount</label>
                                <div class="input-group">
                                    <input type="number" class="form-control receieved_amount" value="{{ $order->received_amount }}" readonly>
                                </div>
                            </div>
                            <div class="form-group col-10">
                                <label for="discount">Discount Percentage</label>
                                <div class="input-group">
                                    <input type="number" class="form-control discountpercentage" value="{{ $order->discount_percentage }}" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
    {{-- <script>
        $(document).ready(function(){
            $('.item').each(function() {
                var price = $(this).find('.price').val();
                var quantity = $(this).find('.quantity').val();
                var totalPrice = price * quantity;
                $(this).find('.totalprice').val(totalPrice);
            });
        });
    </script> --}}

    <script>
        $(document).ready(function(){
            var subtotal = 0;
    
            $('.item').each(function() {
                var price = parseFloat($(this).find('.price').val()) || 0;
                var quantity = parseInt($(this).find('.quantity').val()) || 0;
                var totalPrice = price * quantity;
                $(this).find('.totalprice').val(totalPrice);
                subtotal += totalPrice; // Accumulate subtotal
            });
    
            // Set the subtotal in the corresponding field
            $('.showprice').text(subtotal);
    
            // Calculate discount and total
            var discountPercentage = parseFloat($('.discountpercentage').val()) || 0;            
            var discountAmount = (subtotal * discountPercentage) / 100;
            var total = subtotal - discountAmount;
    
            // Display discount and total
            $('.showdiscount').text(discountAmount);
            $('.showtotalprice').text(total);
        });
    </script>
    
@endsection
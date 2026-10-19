@extends('layouts.master')

@section('title')
Best Uniform | Return Order Page
@endsection
@section('css')   
    <link href="{{ asset('asset/css/returnOrder.css') }}" rel="stylesheet">
@endsection
@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Returns</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Returns</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="container">
                <div class="card ">
                    <div class="card-header" >
                        <div class="row">
                            <div class="col-md-9">
                                <h3 class="card-title mt-2">Return Orders</h3>
                            </div>
                            <div class="col-md-3 d-flex search-area">
                                <div class="wrapper d-flex" >
                                    <input name="order-id" class="search form-control order-id py-1" placeholder="Order-Id">
                                    <i class='fas fa-search order-check-btn'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('return.create') }}" method="POST" id="return">
                            @csrf
                            <div class="row mt-3" id="main-info" style="display:none;">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="school">School</label>
                                        <input type="text" name="school" class="form-control school"  readonly>
                                    </div>
                                </div>
                                <div class="col-md-4 ">
                                    <div class="form-group">
                                        <label for="name">Customer Name</label>
                                        <input type="text" name="name" class="form-control customer-name" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4 ">
                                    <div class="form-group">
                                        <label for="name">Phone</label>
                                        <input type="tel" name="phone" class="form-control phone" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="items-container mt-2">      
                                <div class="order-not-found">Search by Order Id</div>
                            </div>
                            <div class="row mt-3" id="total-row"style="display:none;">
                                <div class="col-md-10" style="text-align:right"><label for="name">Total : </label></div>
                                <div class="col-md-2"> <input type="text" name="grand_total" id="grand_total"  class="form-control grand_total" readonly></div>
                            </div>
                    </div>
                    <div class="card-footer" id="sBtn" style="display:none;">
                        <button type="submit" class="btn returnBtn" id="return" disabled >Submit</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>

        function returnTotal(id){
            price = $('#price_'+id).val()
            discount = $('#discount_'+id).val()
            returnable = $('#return_'+id).val()
            return_total = (price-discount)*returnable
            $('#return_total_'+id).val(return_total)
        }
        function qtyCheck(id){
            qty = $('#qty_'+id).val()
            returnValue = $('#return_'+id).val()
            returnedValue = $('#returned_'+id).val()
            
            if(qty == returnedValue){
                $('#return_'+id).prop('disabled', true);
                $('#return_'+id).prop('placeholder', 'N/A');
                $('#return_'+id).css('backgroundColor', '#fffff');
            }

        }
     
        $(document).ready(function(){
            $('.returnBtn').prop('disabled', true);
            $("#return").on('submit', function(event) {
                event.preventDefault();
                    $.ajax({
                        url: "{{ route('return.create') }}",
                        data: $(this).serialize(),
                        dataType: 'json',
                        type: 'POST',
                        success: function(result) {
                            toastr.success('Order Returned  Successfully');
                        },
                        error: function(response) 
                        {
                                toastr.error(response.responseJSON.error);
                        }
                        
                    });
                });
            
            $('body').on('change', '.return.xyz' ,function(){
                disableButton = true;
                $(".return.xyz").each(function () {
                    if($(this).val() != 0){
                        disableButton = false;
                    }
                });
                $('.returnBtn').prop('disabled', disableButton);
            });
            $('body').on('change', '.return.xyz' ,function(){
                total_return = 0;
                $(".return_total").each(function () {
                    console.log($(this).val());
                    total_return += parseInt($(this).val());
                    console.log('total_return',total_return);
                    $('.grand_total').val(total_return);
                });
            });
            let orderItemRowNumber = 1;
            function calculateTotal() {
                let subtotal = 0; 
                $('.items-row').each(function() {
                    let price = parseFloat($(this).find('.price').val()) || 0; 
                    let quantity = parseInt($(this).find('.quantity').val()) || 0;
                    let total = price * quantity; 
                    $(this).find('.total').val(total); 
                    subtotal += total; // Add item total to the subtotal
                });

                // Display the subtotal
                $('.showprice').text(subtotal.toFixed(2));  
                // Get received amount and calculate remaining amount
                let receivedAmount = parseFloat($('.receivedamount').val()) || 0;
                let remainingAmount = subtotal - receivedAmount; 
                // Display calculated values
                $('.showtotalprice').text(subtotal.toFixed(2)); 
                $('.remainingAmount').text(remainingAmount.toFixed(2)); 
                $('.remainingpayment').val(remainingAmount.toFixed(2)); 
            }
            $('.order-check-btn').on('click', function() {
                const orderId = $('.order-id').val();
                if (orderId) {
                    $.ajax({
                        url: `/getOrder/${orderId}`,
                        method: 'GET',
                        success: function(response) {
                            if (response.success) {
                                const order = response.order;
                                $('#main-info').show();
                                $('#sBtn').show();
                                $('#total-row').show();
                                // Populate customer and school information
                                $('.school').val(order[0].school);
                                $('.customer-name').val(order[0].customer_name);
                                $('.phone').val(order[0].phone);
                                $('.showprice').text(order.subtotal);
                                // Clear previous order items 
                                $('.items-container').empty();
                               order.forEach((item, i) => {
                                const rowHtml = `
                                <div class="row-wrapper" >
                                    <div class="item row items-row ">
                                        <div class="col-md-2 school-col">
                                            <div class="form-group">
                                                <label for="school">School</label>
                                                <input type="text" name="schoolname[${item.order_item_id}]" class="form-control school-name" style="background-color#fffff;" value="${item.oi_school}" readonly>
                                                <input type="hidden" name="order-item[]" class="form-control order-item" value="${item.order_item_id}" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-2 product-col">
                                            <div class="form-group">
                                                <label for="product">Product</label>
                                                <input type="text" name="product[${item.order_item_id}]" class="form-control product" value="${item.product}" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-1 size-col">
                                            <div class="form-group">
                                                <label for="size">Size</label>
                                                <input type="text" name="size[${item.order_item_id}]" class="form-control size" value="${item.size}" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-1 price-wrapper">
                                            <div class="form-group">
                                                <label for="price">Price</label>
                                                <input type="number" id ="price_${i}" name="price[${item.order_item_id}]" class="form-control price" value="${parseFloat(item.price) + parseFloat(item.school_margin)/parseFloat(item.quantity)}" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-1 discount-wrapper">
                                            <div class="form-group">
                                                <label for="discount">Discount</label>
                                                <input type="number" id ="discount_${i}" name="discount[${item.order_item_id}]" class="form-control price" value="${item.discount}" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-1 quantity-col">
                                            <div class="form-group">
                                                <label for="quantity">Quantity</label>
                                                <input type="number" id ="qty_${i}" name="quantity[${item.order_item_id}]" class="form-control quantity" value="${item.quantity}" readonly>
                                            </div>
                                        </div>
                                         <div class="col-md-1 returned-col">
                                            <div class="form-group">
                                                <label for="size">Returned</label>
                                                <input type="text" id ="returned_${i}" name="returned[${item.order_item_id}]" class="form-control returned" value="${item.returned}" readonly>
                                            </div>
                                        </div> 
                                         <div class="col-md-1 returned-col">
                                            <div class="form-group">
                                                <label for="return_total">R-Total</label>
                                                <input type="text" id ="return_total_${i}" name="return_total[${item.order_item_id}]" class="form-control return_total" value="0" readonly>
                                            </div>
                                        </div> 
                                      
                                        <div class="col-md-2 total-wrapper">
                                            <div class="form-group">
                                                <label for="total">Total Price</label>
                                                <input type="number" name="total[${item.order_item_id}]" class="form-control total" value="${ item.total }" readonly>
                                            </div>
                                        </div>
                                          <div class="col-md-1 return-wrapper">
                                            <div class="form-group">
                                                <label for="return">Return</label>
                                                <input type="number" id ="return_${i}" name="return[${item.order_item_id}]" class="form-control return xyz" value="0" min="0" max="${item.quantity-item.returned}" onchange = "qtyCheck(${i});returnTotal(${i});" >
                                                <div id="na_${i}" class="return na">N/A</div>
                                            </div>
                                        </div>
                                    </div>
                                        </div>`;
                            total_return = 0;
                            $(".return_total").each(function () {
                                console.log($(this).val());
                                total_return += parseInt($(this).val());
                                console.log('total_return',total_return);
                                $('.grand_total').val(total_return);
                            });
                                // Append the populated row
                                $('.items-container').append(rowHtml);
                                qty = $('#qty_'+i).val()
                                returnValue = $('#return_'+i).val()
                                returnedValue = $('#returned_'+i).val()
                                returnedValue = $('#returned_'+i).val()
                                
                                if(qty == returnedValue){
                                    $('#return_'+i).hide();
                                    $('#na_'+i).show();
                                    $('#return_'+i).css('backgroundColor', '#ffffff');
            }
                            });
                            } else {
                                toastr.error('Order Not Found.');
                                clearFields(); // Clear fields if order not found
                            }
                        },
                        error: function(xhr) {
                            toastr.error('Order Not Found');
                            $('#main-info').hide();
                            $('#sBtn').hide();
                            $('.items-container').html(`<div class="order-not-found">Order Not Found</div>`);
                        }
                    });
                }
            });
        });
    </script>
@endsection


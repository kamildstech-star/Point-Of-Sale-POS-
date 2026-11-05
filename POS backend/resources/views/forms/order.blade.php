@extends('layouts.master')

@section('title')   
    Best Uniform | Order form
@endsection
@section('css')   
    <link href="{{ asset('asset/css/order.css') }}" rel="stylesheet">
    <link href="{{ asset('asset/css/switchToggle.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Orders</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Orders</li>
                    </ol>
                </div>
                         
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header" >
                <div class="row">
                    <div class="col-md-9 ">
                        <h3 class="card-title mt-2">Add Orders</h3>
                    </div>
                    <div class="col-md-3 d-flex search-area ">
                        <form id="form-pdoduct-scanner">
                            <div class="form-group d-flex">
                                <input id="scanner"  type="text" class="form-control search">
                                <button type="button" class="btn btn-primary">
                                    <i class='fas fa-search order-check-btn'></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <form action="{{ route('order.create') }}" method="POST" id="order">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="school">School</label>
                                <select name="school1" id="school1" class="form-control school1">
                                    <option value="0">Select school</option>
                                    @foreach ($schools as $school)
                                        <option value="{{$school->id}}" {{ old('school1') == $school->id ? 'selected' : ''}}>
                                            {{ $school->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 customer-col">
                            <div class="form-group">
                                <label for="name">Customer Name</label>
                                <div id="wrapper">
                                    <input type="text" name="name" class="form-control name" placeholder="Name" id="name" >
                                            <ul id="customer-suggestion"></ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="name">Phone</label>
                                <div id="phone-wrapper">
                                    <input type="tel" name="phone" class="form-control phone" placeholder="Phone" id="phone">
                                    <ul id="phone-suggestion"></ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1 mt-auto">
                            <div class="form-group">
                                <button type="button" class="btn btn-success sm-btn add">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="copy mt-5">
                        <div class="copy-stock-row form-group" style="border-bottom: 1px solid #ced4da">
                        </div>
                    </div>

                    <div class="row mt-5">                         
                        <div class="col-md-3 ml-1">
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="subtotal" class="font-weight-bold">Sub Total :</label>
                                <span class="showprice" name="showprice">0</span>
                            </div>
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="discount" class="font-weight-bold">Discount :</label>
                                <span class="showdiscount">0</span>
                            </div>
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="total" class="font-weight-bold">Total :</label>
                                <span class="showtotalprice">0</span>
                            </div>
                            <div class="form-group receivedpayment d-none" style="border-bottom: 1px solid #ced4da">
                                <label for="receivedAmount" class="font-weight-bold ">Received :</label>
                                <span class="received">0</span>
                            </div>
                            <div class="form-group returnamount d-none" style="border-bottom: 1px solid #ced4da">
                                <label for="returnedAmount" class="font-weight-bold ">Returned :</label>
                                <span class="returned">0</span>
                            </div>
                            <div class="form-group remaining d-none" style="border-bottom: 1px solid #ced4da">
                                <label for="remainingAmount" class="font-weight-bold">Remaining :</label>
                                <span class="remainingAmount">0</span>
                                <input type="hidden" name="remainingamount" class="remainingpayment">
                            </div>
                        </div>
                        <div class="form-group col-4 mr-3 ml-md-auto">
                            <div>
                                <div>
                                    <label for="receivedAmount">Received Amount</label>
                                    <input type="number" id="receivedamount" name="receivedamount" class="form-control receivedamount" placeholder="Received Amount">
                                </div>
                            </div>
                            <label class=" mt-2" for="discount">Discount Percentage</label>
                            <div class="input-group">
                                <input type="number" name="discountpercentage" class="form-control discount1" placeholder="Discount Percentage">
                                <span class="input-group-text" id="basic-addon2" style="border:0px;border-radius:0">% </span>
                            </div>
                            
                            <div class="form-group">
                                 <label class=" mt-2" for="cardPayment">Credit Card Payment</label>
                                <label class="switch">
                                    <input type="checkbox" name="cardPayment" id="cardPayment"  class="cardPayment" >
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="form-group col-12">
                        <button type="button" class="btn random" style="background-color: rgb(223, 197, 148);font-weight:600">Random Product</button>
                    </div> --}}
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn " >Submit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- <div class="copy2 d-none">
            <div class="form-group cloned-row" style="border-bottom: 1px solid #ced4da">
                <div class="row random-row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="product">Product</label>
                            <input type="text" class="form-control product" name="random[0][product]" style="width: 100%;" id="random-0-product">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" name="random[0][price]" class="form-control price" id="random-0-price">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" name="random[0][quantity]" class="form-control quantity" placeholder="Enter Quantity" value="0" id="random-0-quantity">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="total">Total Price</label>
                            <input type="number" name="random[0][total]" class="form-control total" id="random-0-total" readonly>
                        </div>
                    </div>
                    <div class="col-md-1 mt-auto">
                        <div class="form-group">
                            <button type="button" class="btn btn-danger sm-btn remove">-</button>
                        </div>
                    </div>
                </div>
            
        </div>
    </div> --}}
</div>
@endsection
@section("script")
    <script> 
    stocks = {!! $stocks !!}
        $(document).ready(function(){
            $('#phone').on('keyup',function(){
                  phone = $(this).val();
                  phone_ul.html("")
                  console.log($(this).serialize());
                  if(phone.length == 3){
                    $.ajax({
                            url: "{{ route('phoneCheck') }}",
                            data: {
                                "_token": "{{ csrf_token() }}",
                                 "phone": phone
                            },
                            dataType: 'json',
                            type: 'POST',
                            success: function(result) {
                                console.log(result);
                                phone_ul.html("")
                                result.forEach((element) => {
                                    console.log(element.name)
                                    if(element.name != null){
                                        li =document.createElement("li")
                                        li.textContent = element.name+'|'+element.phone
                                        console.log(li);
                                        phone_ul.append(li)
                                    }
                                });
                            },
                            error: function(response) {
                                if (response.status === 422) {
                                    var errors = response.responseJSON.errors

                                    $.each(errors, function(field, messages) {
                                        var elmId = field.replace(/\./g, '-'); // Replace dots with dashes
                                        var element = $('#' + elmId);
                                    });
                                    toastr.error('Invalid data');
                                }
                            }
                        });
                  }
            })
            phone_number = $('#phone');
            phone_ul = $('#phone-suggestion');
            $('#name').on('keyup',function(){
                  name = $(this).val();
                  ul.html("")
                    $.ajax({
                            url: "{{ route('nameCheck') }}",
                            data: {
                                "_token": "{{ csrf_token() }}",
                                 "customer_name": name
                            },
                            dataType: 'json',
                            type: 'POST',
                            success: function(result) {
                                console.log(result);
                                ul.html("")
                                result.forEach((element) => {
                                    console.log(element.phone)
                                    if(element.phone != null){
                                        li =document.createElement("li")
                                        li.textContent = element.name+'|'+element.phone
                                        console.log(li);
                                        ul.append(li)
                                    }
                                });
                            },
                            error: function(response) {
                                if (response.status === 422) {
                                    var errors = response.responseJSON.errors
                                    $.each(errors, function(field, messages) {
                                        var elmId = field.replace(/\./g, '-'); // Replace dots with dashes
                                        var element = $('#' + elmId);

                                    });
                                    toastr.error('Invalid data');
                                }
                            }
                        });

            })
            customer_name = $('#name');
            ul = $('#customer-suggestion');
            $(ul).on('click',(event)=>{
                console.log(event.target.tagName)
                if(event.target.tagName === 'LI'){
                    name_and_phone = event.target.textContent
                    name_and_phone = name_and_phone.split('|')
                    name = name_and_phone[0]
                    phone = name_and_phone[1]
                    customer_name.val(name)
                    phone_number.val(phone)
                    ul.html('')
                }
            })
            $(phone_ul).on('click',(event)=>{
                console.log(event.target.tagName)
                if(event.target.tagName === 'LI'){
                    name_and_phone = event.target.textContent
                    name_and_phone = name_and_phone.split('|')
                    name = name_and_phone[0]
                    phone = name_and_phone[1]
                    customer_name.val(name)
                    phone_number.val(phone)
                    phone_ul.html('')
                }
            })
            var quantitiy = $(".quantity").val(1);
            let orderItemRowNumber = 1;
            let orderRandomRowNumber = 1;
            schoolData = {!! $schools !!};
            productsData = {!! $products !!};
            stocks = {!! $stocks !!}
            sizes = {!! $sizes !!}
            init();

            function updateStock (schoolId, prdId, sizeId, qty, type) {
                let tempStcok = [];
                for (let i = 0; i < stocks.length; i++) {
                    var stock = stocks[i];
                    if(stock.school_id == schoolId && stock.product_id == prdId && stock.size_id == sizeId && stock.quantity > 0 && stock.quantity > qty){
                        let quantity = stock.quantity;
                        if(type == false){
                            quantity = quantity - qty;
                        } else if (type == true) {
                            quantity = quantity + qty;
                        }
                        tempStcok.push({...stock, quantity: quantity});
                        break
                    }
                }
            }
            function findStock(schoolId, prdId, sizeId) {
                var filterStock = undefined;
                for (let i = 0; i < stocks.length; i++) {
                    var stock = stocks[i];
                    if(stock.school_id == schoolId && stock.product_id == prdId && stock.size_id == sizeId && stock.quantity > 0){
                        filterStock = stock;
                        break
                    }
                }
                return filterStock;
            }
            function getRow(id, schoolId = 0, productId = 0, sizeId = 0){
                let schools = '';
                let products = '';
                let sizes = productId ? getProductSizes(productId, sizeId) : '';
                schoolData.map(function(school){
                    schools += `<option ${schoolId == school.id ? 'selected' : ''} value="${school.id}">
                        ${school.name}
                    </option>`;
                })
                
                productsData.map(function(product){
                    products += `<option ${productId == product.id ? 'selected' : ''} value="${product.id}">
                        ${product.name}
                    </option>`
                });

                var html = `<div class="copy-stock-row cloned-row form-group" style="border-bottom: 1px solid #ced4da">
                                <div row-num="${id}" class="row item default">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="school"> School</label>
                                            <select name="items[${id}][school]" class="form-control school2" id="items-${id}-school">
                                                <option value="0">Select School</option>
                                                ${schools}
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="product"> Product</label>
                                            <select name="items[${id}][product]" class="form-control product product1" id="items-${id}-product">
                                                <option value="0">Select Product</option>
                                                ${products}
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="size">Sizes</label>
                                            <select name="items[${id}][size]" class="form-control size" id="items-${id}-size">
                                                ${sizes}
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="price">Price</label>
                                            <input type="number" name="items[${id}][price]" class="form-control price" id="items-${id}-price" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label for="quantity">Quantity</label>
                                            <input type="number" name="items[${id}][quantity]" class="form-control quantity" id="items-${id}-quantity" placeholder="Enter Quantity" value="1" min="1" onchange="stockQuantityCheck(${id})">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="total">Total Price</label>
                                            <input type="number" name="items[${id}][total]" class="form-control total" id="items-${id}-total" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-1 mt-auto">
                                        <div class="form-group">
                                            <button type="button" class="btn btn-danger sm-btn remove">-</button>
                                        </div>
                                    </div>
                                </div>
                            </div>`;

                return html;
            }
            function calculateTotal(context) {
                var parentRow = context.closest('.row');
                var price = parentRow.find('.price').val();
                var qty = parentRow.find('.quantity').val();
                var finalPrice = price * qty;
                var subTotal = isNaN(finalPrice) ? '0.00' : finalPrice.toFixed(2);
                
                parentRow.find('.total').val(subTotal);
                updateTotals();
            }
            function updateTotals() {
                var discountpercentage = parseFloat($('input[name="discountpercentage"]').val()) || 0;
                var subtotal = 0;
                var discount = 0;
                var total = 0;

                $('.copy-stock-row .row.item').each(function() {
                    subtotal += parseFloat($(this).find('.total').val());
                });

                discount = (subtotal * discountpercentage) / 100;
                total = subtotal - discount;

                $('.showprice').text(subtotal.toFixed(2)); // Ensures 2 decimal places
                $('.showdiscount').text(discount.toFixed(2));
                $('.showtotalprice').text(total.toFixed(2));

                var receivedAmount = parseFloat($('.receivedamount').val());
                
                $('.received').text('0.00');
                $('.returned').text('0.00');
                $('.remainingAmount').text('0.00');

                if(!isNaN(receivedAmount)){
                    if (receivedAmount >= 0) {
                        var totalPrice = parseFloat($('.showtotalprice').text()) || 0;
                        if (receivedAmount >= totalPrice) {
                            // If received amount is greater than or equal to total price
                            $('.received').text(totalPrice.toFixed(2));
                            var returned = receivedAmount - totalPrice;
                            $('.returned').text(returned.toFixed(2));
                            $('.remaining').addClass('d-none');
                            $('.returnamount').removeClass('d-none');
                        } else {
                            // If received amount is less than total price
                            $('.received').text(receivedAmount.toFixed(2));
                            var remainingAmount = totalPrice - receivedAmount;
                            $('.remainingAmount').text(remainingAmount.toFixed(2));
                            $('.remaining').removeClass('d-none');
                            $('.remainingpayment').val(remainingAmount);
                            $('.returnamount').addClass('d-none');
                        }
                    } 
                } else {
                    $('.received').text('0.00');
                    $('.returned').text('0.00');
                    $('.remainingAmount').text('0.00');
                    $('.remaining').addClass('d-none');
                    $('.returnamount').addClass('d-none');
                    $('.receivedpayment').addClass('d-none');  
                } 
            }
            function init(){
                $('.school1').select2({
                    placeholder: "Select School",
                    theme: "bootstrap4",
                    dropdownSearch: true 
                });
                $('.school2').select2({
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
            }
            function initRowSelect2(rowNum) {
                let row = $(`[row-num=${rowNum}]`);
                row.find('.school2').select2({
                    placeholder: "Select School",
                    theme: "bootstrap4", 
                    dropdownSearch: true 
                });
                row.find('.product').select2({
                    placeholder: "Select Product",
                    theme: "bootstrap4", 
                    dropdownSearch: true 
                });
                row.find('.size').select2({
                    placeholder: "Select Size",
                    theme: "bootstrap4", 
                    dropdownSearch: true 
                });
            }
            function getProductSizes (productId, sizeId = 0) {
                let html = '<option  value="0">Select Size</option>';
                sizes.map(function(size, i){
                    if(size.product_id == productId)
                        html += `<option ${sizeId == size.id ? 'selected' : ''} value="${size.id}">${size.size}</option>`;
                });

                return html;
            }
            function stockQuantityCheck(id){
                quantity = $('#items-'+id+'-quantity').val()
                schoolId = $('#items-'+id+'-school').val()
                productId = $('#items-'+id+'-product').val()
                sizeId = $('#items-'+id+'-size').val()
                let isStockAvailable = true;

                let filterStock = findStock(schoolId, productId, sizeId);
                let stockQty = filterStock.quantity;

                if(stockQty < quantity){
                    isStockAvailable = false;
                    toastr.options = {
                        "closeButton": true,
                        "progressBar": true
                    }
                    toastr.error(stockQty+' items are available in stock');
                    $('#items-'+id+'-quantity').val(stockQty)
                }

                return isStockAvailable; 
            }

            $('.card-body').on('change', '.product', function(){
                var productId = $(this).val();
                var sizeDropdown = $(this).closest('.row').find('.size');

                sizeDropdown.html(getProductSizes(productId));
            });

            $('.card-body').on('change', '.school2, .product, .size', function() {
                var parentRow = $(this).closest('.row');
                var schoolVal = parseInt(parentRow.find('.school2').val(), 10);
                var productVal = parseInt(parentRow.find('.product').val(), 10);
                var sizeVal = parseInt(parentRow.find('.size').val(), 10);
                var schoolPercentage = schoolData.find((e) => e.id == (schoolVal || 0));
                var parrentRowNum = parentRow.attr('row-num');

                if (schoolVal > 0 && productVal > 0 && sizeVal > 0) {
                    var filterStock = findStock(schoolVal, productVal, sizeVal);
                    
                    if(filterStock == undefined){
                        toastr.options = {
                            "closeButton": true,
                            "progressBar": true
                        }
                        toastr.error('Stock is not available');
                        parentRow.find('.price').val(0);
                        parentRow.find('.quantity').val(0);
                        calculateTotal(parentRow);
                        return;
                    }

                    var price = parseFloat(filterStock.sale_price);
                    var margin = schoolPercentage ? (price * schoolPercentage.percentage / 100) : 0;
                    var finalprice = margin + price;
                    var qty = parentRow.find('.quantity').val();
 
                    var alreadyExist = false;
                    $('.copy-stock-row .row,.item').each(function() {
                        var rowNum = $(this).attr('row-num');
                        var existedSchool = $(this).find('.school2').val();
                        var existedProduct = $(this).find('.product').val();
                        var existedSize = $(this).find('.size').val();

                        if (schoolVal == existedSchool && productVal == existedProduct && sizeVal == existedSize && parrentRowNum != rowNum) {
                            alreadyExist = $(this);
                            return false;
                        }
                    });

                    if(alreadyExist){
                        let existedRowNum = alreadyExist.attr('row-num');
                        let isStockAvailable = stockQuantityCheck(existedRowNum);
                        if(isStockAvailable) {
                            var existedQuantity = alreadyExist.find('.quantity');
                            existedQuantity.val((parseInt(existedQuantity.val()) + 1));
                            calculateTotal(alreadyExist);
                            parentRow.find('.quantity').val(0);
                            parentRow.find('.price').val(0);
                            parentRow.find('.total').val(0);
                        } else {
                            return;
                        }
                    } else {
                        if(qty == 0){
                            parentRow.find('.quantity').val(1);
                        }
                        parentRow.find('.price').val(finalprice.toFixed(2));
                        calculateTotal(parentRow);
                    }
                } else {
                    parentRow.find('.price').val(0);
                    parentRow.find('.quantity').val(0);
                }
            });
            $(".add").on("click", function() {
                var selectedSchool = $(".school2:last").val();
                var row = getRow(orderItemRowNumber);
                $(".copy").append(row);                
                $(".copy").find('.school2:last').val(selectedSchool);

                initRowSelect2(orderItemRowNumber);
                orderItemRowNumber++; 
            });

            $("body").on("click",".remove",function(){
                $(this).closest(".cloned-row").remove();
                updateTotals();
            });
            // $(".random").on("click", function() {
            //     var random = $(".copy2").clone().removeClass('copy2 d-none');
            //     var randomProductInputName = `random[${orderRandomRowNumber}][product]`;
            //     var randomPriceInputName = `random[${orderRandomRowNumber}][price]`;
            //     var randomQuantityInputName = `random[${orderRandomRowNumber}][quantity]`;
            //     var randomTotalInputName = `random[${orderRandomRowNumber}][total]`;
            //     var randomProductInputId = `random-${orderRandomRowNumber}-product`;
            //     var randomPriceInputId = `random-${orderRandomRowNumber}-price`;
            //     var randomQuantityInputId = `random-${orderRandomRowNumber}-quantity`;
            //     var randomTotalInputId = `random-${orderRandomRowNumber}-total`;


            //     random.find(".product").attr({name: randomProductInputName, id: randomProductInputId});
            //     random.find(".price").attr({name: randomPriceInputName, id: randomPriceInputId});
            //     random.find(".quantity").attr({name: randomQuantityInputName, id: randomQuantityInputId});
            //     random.find(".total").attr({name: randomTotalInputName, id: randomTotalInputId});
        
            //     random.find(".quantity").val('1'); 
                
            //     $('.copy').append(random);
            //     orderRandomRowNumber++;
            // });
            $('.card-body').on('input', '.quantity', function(){
                let currentRow = $(this);
                let rowNum = currentRow.closest('.row').attr('row-num');
                
                stockQuantityCheck(rowNum);
                calculateTotal(currentRow);
            });
            $('.card-body').on('input', '.price', function(){
                calculateTotal($(this));
            });
            $(".discount1").on('input', function(){
                updateTotals();
            });            
            
            $("#order").on('submit', function(event) {
                event.preventDefault();
                $('.form-control').css({'border-color': '', 'border-width': ''}); // Reset all borders
                $('.form-control').closest('.form-group').find('span').css({'border-color': '', 'border-width': ''});

                $.ajax({
                    url: "{{ route('order.create') }}",
                    data: $(this).serialize(),
                    dataType: 'json',
                    type: 'POST',
                    success: function(result) {
                        toastr.success('Order Created Successfully');
                        var invoiceUrl = "{{ route('invoice', ':orderId') }}".replace(':orderId', result.order_id);
                        var newTab = window.open(invoiceUrl, '_blank');
                        location.reload();
                        newTab.focus();
                        $(this)[0].reset();
                    },
                    error: function(response) {
                        if (response.status === 422) {
                            var errors = response.responseJSON.errors

                            $.each(errors, function(field, messages) {
                                var elmId = field.replace(/\./g, '-'); // Replace dots with dashes
                                var element = $('#' + elmId);

                                if (element.is('select')){
                                    element = element.closest('.form-group').find('span');
                                }

                                element.css({
                                    'border-color': 'red',
                                    'border-width': '2px'
                                });
                            });
                            toastr.error('Invalid data');
                        }
                    }
                });
            });
            $('.card-body').on('input', '.receivedamount, input[name="discountpercentage"]', function() {
                $('.receivedpayment').removeClass('d-none');
                updateTotals();
            });

            $("#form-pdoduct-scanner").on('submit', function(e){
                e.preventDefault();
                let scannerValue = $('#scanner').val();
                $('#form-pdoduct-scanner')[0].reset();

                var filterStock = undefined;
                for (let i = 0; i < stocks.length; i++) {
                    var stock = stocks[i];
                    if(stock.product_code == scannerValue && stock.quantity > 0){
                        filterStock = stock;
                        break
                    }
                }

                if(filterStock == undefined){
                        toastr.options = {
                        "closeButton": true,
                        "progressBar": true
                    }
                    toastr.error('Stock is not available');
                    return;
                }

                let productId = filterStock.product_id;
                let schoolId = filterStock.school_id;
                let sizeId = filterStock.size_id;
                let selectedSchool = undefined;
                let selectedProduct = undefined;
                let selectedSize = undefined;

                let existingRow = 0;
                $(".copy-stock-row .row.item").each(function(){
                    let currentRow = $(this);
                    selectedSchool = parseInt(currentRow.find('.school2').val(), 10);
                    selectedProduct = parseInt(currentRow.find('.product').val(), 10);
                    selectedSize = parseInt(currentRow.find('.size').val(), 10);

                    if (selectedSchool == schoolId && selectedProduct == productId && selectedSize == sizeId) {
                        existingRow = currentRow;
                        return false;
                    }                  
                })

                if(existingRow){
                    let existedRowNum = existingRow.attr('row-num');
                    let isStockAvailable = stockQuantityCheck(existedRowNum);

                    if(isStockAvailable) {
                        let quantity = existingRow.find('.quantity');
                        let currentQty = parseInt(quantity.val());
                        quantity.val(currentQty+1);
                        calculateTotal(existingRow);
                        toastr.options = {
                            "closeButton": true,
                            "progressBar": true
                        }
                        toastr.success('Product added with existing Item.');
                    } else {
                        return;
                    }
                } else {
                    let lasRowSelectedSchool = $(".school2:last").val();
                    let schoolPercentage = schoolData.find((e) => e.id == (schoolId || 0));
                    let qty = 1;
                    let price = parseFloat(filterStock.sale_price);
                    let margin = schoolPercentage ? (price * schoolPercentage.percentage / 100) : 0;
                    let finalprice = margin + price;
                    let subTotal = finalprice * qty;
                    subTotal = isNaN(subTotal) ? '0.00' : subTotal.toFixed(2);

                    let row = $(getRow(orderItemRowNumber, schoolId, productId, sizeId));
                    row.find('.price').val(finalprice.toFixed(2));
                    row.find('.quantity').val(qty);
                    row.find('.total').val(subTotal);
                    $(".copy").append(row);
                    
                    updateTotals();
                    
                    initRowSelect2(orderItemRowNumber);
                    orderItemRowNumber++; 
                }
            });
        });    
    </script>
@endsection

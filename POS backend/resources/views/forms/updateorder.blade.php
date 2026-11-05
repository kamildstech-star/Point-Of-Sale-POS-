@extends('layouts.master')

@section('title')
Best Uniform | Edit Order Form
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
            <div class="card card-primary">
                <div class="card-header" style="background-color: #1d1d50">
                    <h3 class="card-title mt-2">Edit Order</h3>
                </div>  
                <form action="{{ route('order.update', ['id' => $orders->id]) }}" method="POST" id="order">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="school">Select School</label>
                                    <select name="school_id" id="school1" class="form-control">
                                        @foreach ($schools as $school)
                                            <option value="{{ $school->id }}" {{ $orders->school_id == $school->id ? 'selected' : '' }}>
                                                {{ $school->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4 ml-md-auto">
                                <div class="form-group">
                                    <label for="name">Customer Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="Enter Name" value="{{ $orders->customer_name }}">
                                </div>
                            </div>
                        </div>
                        <div class="copy mt-5">
                            @php
                                $subtotal = 0
                            @endphp
                            @if ($orders->orderItems && count($orders->orderItems) > 0)
                                @foreach ($orders->orderItems as $index => $item)
                                    <div class="{{ $index == 0 ? 'copy-stock-row' : '' }} form-group hello" style="border-bottom: 1px solid #ced4da">
                                        <div class="row item">
                                            <input type="hidden" name="items[{{ $index }}][id]" class="form-control existing-row-item-id" value="{{ $item->id }}">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="school">Select School</label>
                                                    <select name="items[{{ $index }}][school]" class="form-control school2" id="items-0-school">
                                                        <option value="0">Select School</option>
                                                        @foreach ($schools as $school)
                                                            <option value="{{ $school->id }}" {{ $item->school_id == $school->id ? 'selected' : '' }}>
                                                                {{ $school->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="product">Select Product</label>
                                                    <select name="items[{{ $index }}][product]" class="form-control product product1" id="items-0-product">
                                                        <option value="0">Select Product</option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                                {{ $product->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>                                        
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="size">Sizes</label>
                                                    <select name="items[{{ $index }}][size]" class="form-control size" id="items-0-size">
                                                        <option value="0">Select Size</option>
                                                        @foreach ($sizes as $size)
                                                            <option value="{{ $size->id }}" {{ $item->size_id == $size->id ? 'selected' :  '' }}>
                                                                {{ $size->size }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="price">Price</label>
                                                    <input type="number" name="items[{{ $index }}][price]" id="items-0-price" class="form-control price" value="{{ $item->price }}" readonly>
                                                    <input type="hidden" name="items[{{ $index }}][school_margin]" class="schoolmargin" value="{{ $item->school_margin }}"> 
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <label for="quantity">Quantity</label>
                                                    <input type="number" name="items[{{ $index }}][quantity]" id="items-0-quantity" class="form-control quantity" value="{{ $item->quantity }}" min="1">
                                                </div>
                                            </div>
                                            @php
                                                $subtotal = $item['quantity'] * $item['price']; 
                                                $rowtotal = $subtotal; 
                                            @endphp
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="total">Total Price</label>
                                                    <input type="number" name="items[{{ $index }}][total]" class="form-control total" value="{{ $rowtotal }}" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-1 mt-auto">
                                                <div class="form-group">
                                                    @if ($index == 0)
                                                        <button type="button" class="btn btn-success sm-btn add">+</button>
                                                    @else
                                                        <button type="button" class="btn sm-btn remove btn-danger">-</button>    
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p>No order_items found.</p>
                            @endif
                        </div>
                        <div class="random1 mt-3">
                            @if ($orders->orderRandomItems && count($orders->orderRandomItems) > 0)
                                @foreach ($orders->orderRandomItems as $index => $item)
                                    <div class="{{ $index == 0 ? 'randomrow' : '' }} form-group hello" style="border-bottom: 1px solid #ced4da">
                                        <div class="row random-row">
                                            <input type="hidden" name="random[{{ $index }}][id]" class="form-control" value="{{ $item->id }}">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="product">Product Name</label>
                                                    <input type="text" name="random[{{ $index }}][product]" class="form-control product" value="{{ $item->product }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="price">Price</label>
                                                    <input type="number" name="random[{{ $index }}][price]" class="form-control price" value="{{ $item->price }}">
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <label for="quantity">Quantity</label>
                                                    <input type="number" name="random[{{ $index }}][quantity]" class="form-control quantity" value="{{ $item->quantity }}" min="1">
                                                </div>
                                            </div>
                                            @php
                                                $randomrow = $item['quantity'] * $item['price']; 
                                                $randomrowtotal = $randomrow;
                                            @endphp
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="total">Total Price</label>
                                                    <input type="number" name="random[{{ $index }}][total]" class="form-control total" value="{{ $randomrowtotal }}" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-1 mt-auto">
                                                <div class="form-group">
                                                    <button type="button" class="btn btn-danger sm-btn remove">-</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class="copy2 form-group hello d-none" style="border-bottom: 1px solid #ced4da">
                            <div class="row random-row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="product">Product Name</label>
                                        <input type="text" class="form-control product">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="price">Price</label>
                                        <input type="number" class="form-control price">
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label for="quantity">Quantity</label>
                                        <input type="number" class="form-control quantity" value="1" min="1">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="total">Total Price</label>
                                        <input type="number" class="form-control total" readonly>
                                    </div>
                                </div>
                                <div class="col-md-1 mt-auto">
                                    <div class="form-group">
                                        <button type="button" class="btn btn-danger sm-btn remove">-</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-5">
                            <div class="form-group col-4">
                                <label for="discount">Discount</label>
                                <div class="input-group">
                                    <input type="number" name="discount" class="form-control discount1" value="{{ $orders->discount_percentage }}">
                                    <span class="input-group-text" style="border-radius: 0px">%</span>
                                </div>
                            </div>
                            <div class="col-md-3 ml-md-auto">
                                <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                    <label class="font-weight-bold">Sub Total</label>
                                    <span class="showprice">{{ $orders->subtotal }}</span>
                                    <input type="hidden" name="showsubtotal" id="showsubtotal" value="{{$orders->subtotal;}}">
                                </div>
                                <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                    <label class="font-weight-bold">Discount Total</label>
                                    <span class="discount">{{ $orders->discount }}</span>
                                    <input type="hidden" name="discount_total" id="discount_total" value="{{ $orders->discount_percentage; }}">
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold">Total</label>
                                    <span class="totalprice">{{ $orders->total }}</span>
                                    <input type="hidden" name="total_price" id="total_price" value="{{$orders->total; }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-3">
                            <button type="button" class="btn random" style="background-color: rgb(223, 197, 148);font-weight:600">Random Product</button>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn" id="submit" style="background-color: rgb(223, 197, 148);font-weight:600">Update Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            let orderItemRowNumber = {{ (count($orders->orderItems) > 0) ? count($orders->orderItems) : 1 }};
            let orderRandomRowNumber = {{ (count($orders->orderRandomItems) > 0) ? count($orders->orderRandomItems) : 1 }};
            function fetchPrice(context) {
                var $context = $(context);
                var parentRow = $context.closest('.row');
                var schoolId = parentRow.find('.school2').val();
                var productId = parentRow.find('.product').val();            
                var sizeId = parentRow.find('.size').val();
                $.ajax({
                    url: "{{ route('getprice') }}",
                    type: 'GET',
                data: {
                    school_id: schoolId,
                    product_id: productId,
                    size_id: sizeId
                },
                success: function(response) {
                    if (response.saleprice) {
                        var percentage = parseFloat(response.percentage);                   
                        var price = parseFloat(response.saleprice);
                        var margin = (percentage * (price)/ 100);                    
                        var finalprice = margin + price;                    
                        parentRow.find('.price').val(finalprice);
                        parentRow.find('.schoolmargin').val(margin);
                    } else {
                        parentRow.find('.price').val(0);
                        parentRow.find('.schoolmargin').val(0);
                    }
                        calculateTotal(parentRow);
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    parentRow.find('.price').val(0);
                    parentRow.find('.schoolmargin').val(0);
                    calculateTotal(parentRow);
                }
                });
            }
            $('body').on('change', '.school2, .product, .size', function() {
                fetchPrice(this);            
                updateTotals($(this));
            });
            $(".add").on("click", function() {
                var originalStockRow = $(".copy-stock-row");            
                var stockRow = $(".copy-stock-row").clone().removeClass("copy-stock-row");
                    stockRow.find('.existing-row-item-id').remove();

                var schoolInputName = `items[${orderItemRowNumber}][school]`;
                var productInputName = `items[${orderItemRowNumber}][product]`;
                var sizeInputName = `items[${orderItemRowNumber}][size]`;
                var priceInputName = `items[${orderItemRowNumber}][price]`;
                var marginInputName = `items[${orderItemRowNumber}][school_margin]`;
                var quantityInputName = `items[${orderItemRowNumber}][quantity]`;
                var totalInputName = `items[${orderItemRowNumber}][total]`;
                    var schoolInputId = `items-${orderItemRowNumber}-school`;
                    var productInputId = `items-${orderItemRowNumber}-product`;
                    var sizeInputId = `items-${orderItemRowNumber}-size`;
                    var priceInputId = `items-${orderItemRowNumber}-price`;
                    var quantityInputId = `items-${orderItemRowNumber}-quantity`;
                    var totalInputId = `items-${orderItemRowNumber}-total`;

                var schoolStockRow = stockRow.find(".school2");
                    // schoolStockRow.attr('name', schoolInputName);
                    schoolStockRow.attr({name: schoolInputName, id: schoolInputId});
                    schoolStockRow.val(originalStockRow.find(".school2 :selected").val());
                stockRow.find(".product").attr({name: productInputName, id: productInputId});
                stockRow.find(".size").attr({name: sizeInputName, id: sizeInputId});
                stockRow.find(".price").attr({name: priceInputName, id: priceInputId});
                stockRow.find(".quantity").attr({name: quantityInputName, id: quantityInputId});
                stockRow.find(".total").attr({name: totalInputName, totalInputId});
                stockRow.find(".schoolmargin").attr('name', marginInputName);

                stockRow.find('select:not(.school2, .product, .size), input').each(function() {
                    $(this).val('');
                });

                stockRow.find(".size").find("option:gt(0)").remove();    
                stockRow.find(".quantity").val('1');

                var removeBtn = stockRow.find(".add");
                    removeBtn.html("-").addClass("remove btn-danger").removeClass("btn-success col-md-1");

                $(".copy").append(stockRow);
                orderItemRowNumber++;
       
                stockRow.find('.school2, .product, .size').select2({
                    placeholder: "Select Option",
                    theme: "bootstrap4",
                    dropdownSearch: true
                });
            });
            $("body").on("click",".remove",function(){
                $(this).closest(".hello").remove();
                updateTotals(); 
            });
            $(".random").on("click", function() {            
                var random = $(".copy2").clone().removeClass('copy2 d-none');
                var randomProductInputName = `random[${orderRandomRowNumber}][product]`;
                var randomPriceInputName = `random[${orderRandomRowNumber}][price]`;
                var randomQuantityInputName = `random[${orderRandomRowNumber}][quantity]`;
                var randomTotalInputName = `random[${orderRandomRowNumber}][total]`;

                var randomProductInputId = `random-${orderRandomRowNumber}-product`;
                var randomPriceInputId = `random-${orderRandomRowNumber}-price`;
                var randomQuantityInputId = `random-${orderRandomRowNumber}-quantity`;
                var randomTotalInputId = `random-${orderRandomRowNumber}-total`;

                random.find(".product").attr({name: randomProductInputName, id: randomProductInputId});
                random.find(".price").attr({name: randomPriceInputName, id: randomPriceInputId});
                random.find(".quantity").attr({name: randomQuantityInputName, id: randomQuantityInputId});
                random.find(".total").attr({name: randomTotalInputName, id: randomTotalInputId});

                $(".random1").append(random);
                orderRandomRowNumber++;
            });
            $("#school1").on('change', function(){
                var selectvalue = $(this).val();
                $(".school2").val(selectvalue);
                $(".product").val('0');
                $(".price").val('0');
                $(".quantity").val('0');
                $(".size").val('0');
                $(".total").val('0');
            });
            $('.card-body').on('input', '.quantity,.price', function(){
                calculateTotal($(this));            
            });
            $(".discount1").on('input', function(){
                updateTotals(this);
            });
            function calculateTotal(context) {
                var parentRow = context.closest('.row');
                
                var qty = parseFloat(parentRow.find('.quantity').val()); 
                var price = parseFloat(parentRow.find('.price').val());
                var subTotal = qty * price;
                subTotal = isNaN(subTotal) ? '0.00' : subTotal.toFixed(2);
                parentRow.find('.total').val(subTotal);
                updateTotals($(this));
            }
            function updateTotals() {
                var subtotal = 0;
                var randomSubtotal = 0;
                var discount = parseFloat($('input[name="discount"]').val()) || 0;
                var total = 0;

                // Iterate through regular product rows and calculate subtotal
                $('.copy-stock-row .row,.item').each(function() {
                    var price = parseFloat($(this).find('.price').val()) || 0;
                    var quantity = parseFloat($(this).find('.quantity').val()) || 0;
                    var rowTotal = price * quantity;                
                    $(this).find('.total').val(rowTotal.toFixed(2));
                    subtotal += rowTotal;                    
                });

                // Iterate through random product rows and calculate random subtotal
                $('.random-row').each(function() {
                    var price = parseFloat($(this).find('.price').val()) || 0;
                    var quantity = parseFloat($(this).find('.quantity').val()) || 0;
                    var rowTotal = price * quantity;

                    $(this).find('.total').val(rowTotal.toFixed(2));
                    randomSubtotal += rowTotal;                        
                });

                // Calculate discount amount and total
                var discountAmount = (subtotal + randomSubtotal) * ( discount / 100);
                total = subtotal + randomSubtotal - discountAmount;

                // Update UI with calculated values
                $('.showprice').text((subtotal + randomSubtotal).toFixed(2));
                $('#showsubtotal').val((subtotal + randomSubtotal).toFixed(2));
                $('.discount').text(discountAmount.toFixed(2));
                $('#discount_total').val(discountAmount.toFixed(2));
                $('.totalprice').text(total.toFixed(2));
                $('#total_price').val(total.toFixed(2));
            }
            $('.card-body').on('change', '.product', function(){
                var productId = $(this).val();
                
                var sizeDropdown = $(this).closest('.row').find('.size');
                    sizeDropdown.html('<option>Select Size</option>');
                if (productId != 0)
                $.ajax({
                    url: '{{route('getSizesForProduct')}}',
                    type: 'GET',
                    data: { product_id: productId },
                    success: function(res){
                        $.each(res, function(key, value){
                            sizeDropdown.append('<option value="' + value.id + '">' + value.size + '</option>');
                        });
                    }
                });
            });
            function generateOrderDetail(){
                var billNumber = Math.floor(Math.random() * 1000) + 1;
                var now = new Date();
                var dateTime = now.toLocalString();
                $('#billNumber').text('Bill #' + billNumber);
                $('#dateTime').text('Date/Time:' + dateTime);
            }
            $("#order").on('submit', function(event) {
                 event.preventDefault();
                $('.orderErrorMsg').remove(); // Remove any existing error messages
                $.ajax({
                    url: "{{ route('order.update', ['id' => $orders->id] ) }}",
                    data: $(this).serialize(),
                    dataType: 'json',
                    type: 'POST',
                    success: function(result) {
                        // Redirect to the orderindex route upon successful update
                        window.location.href = "{{ route('invoice', ':orderId') }}".replace(':orderId', result.order_id);
                    },
                    error: function(response) {
                        if (response.status == 422) {
                            var errors = response.responseJSON.errors;
                            $.each(errors, function(elementId, messages) {
                                var elmId = elementId.replace(/\./g, '-'); // Replace dots in element ID to match HTML IDs
                                var element = $('#' + elmId); // Find the corresponding form field
                                var errorDiv = $('<span class="error-message orderErrorMsg"></span>').text(messages[0]);
                                element.after(errorDiv); // Append error message after the field
                            });
                        }
                    }
                });

                generateOrderDetail();
            });
            $('.school2').select2({
                placeholder: "Select School",
                theme: "bootstrap4", // Optional: Change the theme to match Bootstrap 4
                dropdownSearch: true // Enable searching within dropdown
            });
            $('.product1').select2({
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
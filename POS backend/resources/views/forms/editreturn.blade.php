@extends('layouts.master')

@section('title')
Best Uniform | Return Order Page
@endsection

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Edit Returns</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Edit Returns</li>
                    </ol>
                </div>
            </div>
        </div>
    <div class="container">
        <div class="card card-primary">
            <div class="card-header" style="background-color: #1d1d50">
                <h3 class="card-title mt-2">Edit Returns</h3>
            </div>
            <form action="{{ route('return.update', ['id'=> $returns->id]) }}" method="POST" id="return">
                @csrf
                <div class="card-body">
                    <div class="copy mt-5">
                        @if ($returns->returnitems && count($returns->returnitems) > 0)
                            @foreach ($returns->returnitems as $index => $item)
                                <div class="{{ $index == 0 ? 'copy-stock-row' : '' }} form-group hello" style="border-bottom: 1px solid #ced4da">
                                    <div class="row item">
                                        <div class="col-md-2">
                                            <label for="id">Order Id</label>
                                            <input type="number" name="items[{{ $index }}][order-id]" class="form-control order-id" value="{{ isset($item->order_id) ? $item->order_id : '' }}" placeholder="Enter order Id" id="items-{{ $index }}-order-id">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="school">Select School</label>
                                                <select name="items[{{ $index }}][school]" class="form-control school2" id="items-{{ $index }}-school">
                                                    <option value="0">Select School</option>
                                                    @foreach ($schools as $school)
                                                        <option value="{{ $school->id }}" {{ isset($item->school_id) && $item->school_id == $school->id ? 'selected' : '' }}>
                                                            {{ $school->name }}
                                                        </option> 
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="product">Select Product</label>
                                                <select name="items[{{ $index }}][product]" class="form-control product product1" id="items-{{ $index }}-product">
                                                    <option value="0">Select Product</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}" {{ isset($item->product_id) && $item->product_id == $product->id ? 'selected' : '' }}>
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="size">Sizes</label>
                                                <select name="items[{{ $index }}][size]" class="form-control size" id="items-{{ $index }}-size">
                                                    <option value="0">Select Size</option>
                                                    @foreach ($sizes as $size)
                                                        <option value="{{ $size->id }}" {{ isset($item->size_id) && $item->size_id == $size->id ? 'selected' : '' }}>
                                                            {{ $size->size }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label for="price">Price</label>
                                                <input style="width: 80px;background-color: #fff" type="number" name="items[{{ $index }}][price]" value="{{ isset($item->price) ? $item->price : '' }}" id="items-{{ $index }}-price" class="form-control price" readonly >
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label for="quantity">Quantity</label>
                                                <input style="width: 80px" type="number" name="items[{{ $index }}][quantity]" id="items-{{ $index }}-quantity" class="form-control quantity" placeholder="Enter Quantity" value="{{ isset($item->quantity) ? $item->quantity : '' }}" min="1">
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label for="total">Total</label>
                                                <input style="width: 80px;background-color: #fff" type="number" name="items[{{ $index }}][total]" value="{{ isset($item->price) && isset($item->quantity) ? $item->price * $item->quantity : '' }}" id="items-{{ $index }}-total" class="form-control total" readonly>
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
                            <p>No return items found.</p>
                        @endif
                    </div>
                    <div class="row mt-5"> 
                        <div class="col-md-3 mr-3 ml-md-auto">
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="subtotal" class="font-weight-bold">Sub Total</label>
                                <span class="showsubtotal">{{ $returns->subtotal }}</span>
                                <input type="hidden" name="showsubtotal" id="showsubtotal"> 
                            </div>
                            <div class="form-group" style="border-bottom: 1px solid #ced4da">
                                <label for="total" class="font-weight-bold">Total</label>
                                <span class="showtotalprice">{{ $returns->total }}</span>
                                <input type="hidden" name="showtotalprice" id="showtotalprice">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mr-4 text-right">
                        <button type="submit" class="btn" style="background-color: rgb(223, 197, 148);font-weight:600">Return</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            let orderItemRowNumber = {{ (count($returns->returnitems) > 0) ? count($returns->returnitems) : 1 }};
            function fetchPrice(context) {
                var $context = $(context);
                var parentRow = $context.closest('.row');
                var orderId = parentRow.find('.order-id').val(); 
                var schoolId = parentRow.find('.school2').val();
                var productId = parentRow.find('.product').val();            
                var sizeId = parentRow.find('.size').val();
                var quantity = parentRow.find('.quantity').val();
                var priceInput = parentRow.find('.price');
                if(orderId && schoolId && productId && sizeId && quantity){
                    $.ajax({
                        url: "{{ route('fetchPrice') }}",
                        type: 'GET',
                        data: {
                            order_id: orderId,
                            school_id: schoolId,
                            product_id: productId,
                            size_id: sizeId,
                            quantity: quantity
                        },
                        success: function(response) {
                            if(response.price) {
                                priceInput.val(response.price) || 0;
                                calculateTotal(parentRow);
                            } else {
                                priceInput.val(0);
                                console.log("Price not found");
                            }
                        },
                        error: function(xhr) {                        
                            console.log(xhr.responseText);
                        }
                    });
                }
            }
            $('.card-body').on('change', '.order-id, .school2, .product, .size', function() {
                fetchPrice($(this));
            });
            $('.card-body').on('change', '.product', function(){
                var productId = $(this).val();
                var sizeDropdown = $(this).closest('.row').find('.size');
                sizeDropdown.html('<option>Select Size</option>');

                $.ajax({
                    url: '{{route('getSizesForProduct')}}?product_id=' + productId,
                    type: 'GET',
                    success: function(res){
                        $.each(res, function(key, value){
                            sizeDropdown.append('<option value="' + value.id + '">' + value.size + '</option>');
                        });
                    }
                });
            });
            $('.add').on('click', function(){
                var originalstockrow = $('.copy-stock-row');
                var stockrow = originalstockrow.clone().removeClass('copy-stock-row');
                stockrow.find('.form-control').val('');
                stockrow.find('.quantity').val('1');

                var orderIdInputName = `items[${orderItemRowNumber}][order-id]`;
                var schoolInputName = `items[${orderItemRowNumber}][school]`;
                var productInputName = `items[${orderItemRowNumber}][product]`;
                var sizeInputName = `items[${orderItemRowNumber}][size]`;
                var priceInputName = `items[${orderItemRowNumber}][price]`;
                var quantityInputName = `items[${orderItemRowNumber}][quantity]`;
                var totalInputName = `items[${orderItemRowNumber}][total]`;
                var orderInputId = `items-${orderItemRowNumber}-order-id`;
                var schoolInputId = `items-${orderItemRowNumber}-school`;
                var productInputId = `items-${orderItemRowNumber}-product`;
                var sizeInputId = `items-${orderItemRowNumber}-size`;
                var priceInputId = `items-${orderItemRowNumber}-price`;
                var quantityInputId = `items-${orderItemRowNumber}-quantity`;
                var totalInputId = `items-${orderItemRowNumber}-quantity`;   
                    stockrow.find(".school2").val(originalstockrow.find(".school2").val()); // Fixed
                    stockrow.find(".order-id").attr({name: orderIdInputName, id: orderInputId}); // Fixed
                    stockrow.find(".school2").attr({name: schoolInputName, id: schoolInputId});
                    stockrow.find(".product").attr({name: productInputName, id: productInputId});
                    stockrow.find(".size").attr({name: sizeInputName, id: sizeInputId});
                    stockrow.find(".price").attr({name: priceInputName, id: priceInputId});
                    stockrow.find(".quantity").attr({name: quantityInputName, id: quantityInputId});
                    stockrow.find(".total").attr({name: totalInputName, id: totalInputId});

                var removeBtn = stockrow.find(".add");
                    removeBtn.html("-").addClass("remove btn-danger").removeClass("btn-success col-md-1");
                $('.copy').append(stockrow);
                orderItemRowNumber++;
            });
            $('.card-body').on('click', '.remove', function(){
                $(this).closest('.hello').remove();
                calculateTotals();
            });
            $('.card-body').on('change', '.quantity', function(){
                var row = $(this).closest('.row');
                var price = parseFloat(row.find('.price').val()) || 0;
                var quantity = parseFloat($(this).val()) || 1;                
                var total = price * quantity;

                row.find('.total').val(total.toFixed(2));
                calculateTotals();
            });
            function calculateTotals(){
                var subtotal = 0;
                $('.total').each(function(){
                    subtotal += parseFloat($(this).val());
                });
                $('.showsubtotal').text(subtotal.toFixed(2));
                $('#showsubtotal').val(subtotal.toFixed(2));
                $('.showtotalprice').text(subtotal.toFixed(2));
                $('#showtotalprice').val(subtotal.toFixed(2));
            }
            $('#return').on('submit', function(event) {
                event.preventDefault();
                $('.returnErrorMsg').remove();
                $.ajax({
                    url: "{{ route('return.update', ['id' => $returns->id]) }}",
                    data: $(this).serialize(),
                    dataType: 'json',
                    type: 'POST',
                    success: function(result) {
                        alert('Return Order Successfully');
                        window.location.href = "{{ route('returns.index') }}"
                    },
                    error: function(response) {
                        if (response.status == 422) {  // Validation error status code
                            var errors = response.responseJSON.errors;
                            $.each(errors, function(elementId, messages) {
                                var elmId = elementId.replace(/\./g, '-'); 
                                console.log(elmId); // Replace dots with hyphens to match element IDs
                                var element = $('#' + elmId);  // Select the element by its ID
                                var errorDiv = $('<span class="error-message returnErrorMsg text-danger"></span>').text(messages);
                                element.after(errorDiv);  // Insert the error message after the form element
                            });
                        } else {
                            alert('An error occurred while returning the order. Please try again.');
                        }
                    }
                });
            });
            calculateTotals();
            $('.school2').select2({
                placeholder: "Select School",
                theme: "bootstrap4",
                dropdownSearch: true
            });
            $('.product1').select2({
                placeholder: "Select School",
                theme: "bootstrap4",
                dropdownSearch: true
            });
            $('.size').select2({
                placeholder: "Select School",
                theme: "bootstrap4",
                dropdownSearch: true
            });
        });
    </script>
@endsection


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <!-- Bootstrap CSS -->
    <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> -->
    <link href="{{ asset('asset/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- JQUERY -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Custom styles -->
    <style>
        .invoice-container {
            max-width: 397px;
            padding: 0 10px;
            color: #000;
        }
        .card{
            padding: 0  10px;
            border: none;
        }
        .card-header {
            background-color: dimgray;
            color: #fff;
            padding: 0px;
        }
        .logo-Img{
            width: 100%;
            display: flex;
        }
        .logo-Img img{
            width: 20%;
        }
        .logo-Img h3{
            font-size: 45px;
            margin-top: 10px;
        }
        .card-body{
            padding: 0;
        }
        .row{
            margin: 0;
        }
        .shopAdress{
            margin: 0;
            font-size: 15px;
        }
        .shopPhone{
            font-size: 16px;
            margin-bottom: 0;
        }
        .item-row {
            border-bottom: 1px solid;
        }
        .bill-Details p{
            margin-bottom: 0%;
            text-align: center;
        }
        .billNum{
            width: 38%;
            font-size: 13px;
            border: 1px solid;
            border-width: 1px 0px 0px 0px;
        }
        .billNum p{
            font-size: 16px;
            margin-left: 5px;
            margin-right: 5px;
        }
        #billNumber{
            border-right: 1px solid;
            flex-wrap: nowrap;
            font-weight: bold;
            padding-right: 5px;
        }
        #invoiceHeading{
            font-weight: bold;
            margin-bottom: 0;
        }
        .DateTime{
            width: 62%;
            font-size: 14px;
            border: 1px solid;
            border-width: 1px 0px 0px 1px;
        }
        .table{
            margin-bottom: 8px;
        }
        .table td, .table th{
            padding: 5px;
            color: #000;
            border-color: #000;
        }
        .table thead th{
            border-color: #000; 
        }
        #srNum{
            width: 8%;
        }
        .quantity{
            width: 8%; 
        }
        .invoice-total{
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .invoice-total div{
            width: 65%;
            display: flex;
            text-align: center;
            line-height: 0.5;   
            justify-content: flex-end;
            vertical-align: middle;
        }
        .invoice-total div span{
            width: 20%;
            padding: 10px;
            border: 1px solid;
            font-weight: bold;
            text-align: center;
            font-size: 16px;
        }
        .invoice-total div p{
            width: 40%;
            margin-bottom: 0;
            text-align: center;
            padding:10px;
            font-size: 16px;
            border: 1px solid;
            font-weight: bold;
        }
        .invoice-total div strong{
            margin-top: 6px;
            margin-right: 3px;
            font-size: 18px;
        }
        .conditions{
            display: grid;
            font-size: 18px;
        }
        .conditions ul li{
            font-size: 16px;
        }
        #footer{
            width: 100%;
            text-align: center;
            border-top: 1px solid;
            padding: 3px;
            border-bottom: 1px solid;
        }
    </style>
</head>
<body onload="window.print()" onfocus="window.close()">
    <div class="invoice-container">
        <div class="card">
            <div class="card-header d-flex">
                <div class="logo-Img">
                    <img src="{{ asset('asset/img/logo2.png') }}" alt="">
                    <h3>Best Uniform</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <p class="shopAdress">SHOP NO 1, IQBAL STADIUM, FAISALABAD</p>
                    <p class="shopPhone">0320-7755990</p>
                    <h3 id="invoiceHeading">Sale Invoice</h3>
                </div>
                <div class="row bill-Details">
                    <div class="billNum d-flex">
                        <p id="billNumber">Bill #</p>
                        <p>{{ date('Y') }} - {{ $order->id }}</p>
                    </div>
                    <div class="DateTime">
                        <p>Date/Time  : {{ $updated_at }}</p>
                    </div>                    
                </div>
                <div class="row">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->orderItems->concat($order->orderRandomItems) as $index => $item)
                                    <tr class="item-row">
                                        <td id="srNum">{{ $index + 1 }}</td>
                                        <td>
                                            @if(isset($item->school) && isset($item->product))
                                                {{ $item->school->name }} - {{ $item->product->name }}-
                                                Size: {{ $item->size->size }}
                                            @elseif(isset($item->product))
                                                {{ $item->product }}
                                            @endif
                                        </td>
                                        {{-- Calculate and display the price with the school percentage --}}
                                        @php
                                            $schoolPercentage = $order->school ? $order->school->percentage : 0;
                                            $priceWithMarkup = $item->price + (($schoolPercentage * $item->price) / 100);
                                        @endphp
                                        <td>{{ $priceWithMarkup }}</td>
                                        <td class="quantity">{{ $item->quantity }}</td>
                                        <td>{{ $priceWithMarkup * $item->quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                </div>
                <div class="invoice-total">
                    <div>
                        <strong>Subtotal : </strong>
                        <span class="total-quantity"></span><p> {{ $order->subtotal }}</p>
                    </div>
                    <div class="discount-row">
                        <strong>Discount </strong>
                        <p> {{ $order->discount }} </p>
                    </div>
                    <div>
                        <strong>Total </strong>
                        <p> {{ $order->total }} </p>
                    </div>
                </div>                
                <div class="row conditions">
                    <strong class="ml-3">Terms and Condition</strong>
                    <ul class="mb-0">
                        <li>Purchase Good Will be Exchange Within 7 days</li>
                        <li>Imported Items have no guarantee</li>
                        <li>Imported Items Will not be returned</li>
                        <li>Washed Items Will not be returned</li>
                    </ul>
                    <strong id="footer">Thanks For Purchasing</strong>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            
            let totalQuantity = 0;
            $('.quantity').each(function() {
                totalQuantity += parseInt($(this).text()) || 0;
            });
            $('.total-quantity').text(totalQuantity);
            let discount = parseFloat('{{ $order->discount }}') || 0;
            if (discount === 0) {
                $('.discount-row').hide();
            }
            setTimeout(function() {
                window.close();
            }, 5000);  
        });
    </script>
</body>
</html>
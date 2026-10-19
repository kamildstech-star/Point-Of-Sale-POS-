<!DOCTYPE html>
<html lang="en">
<head>
    <title>Title</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous"> -->
    <link href="{{ asset('asset/css/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #eee;
        }
        .logo div{
            width: 97%;
            background-color: #1d1d50;
            text-align: center;
        }
        .logo img {
            width: 30%; /* Adjust width as needed */
        }
        .footer {
            background-color: #1d1d50;
            color: rgb(223, 197, 148);
            padding: 10px 20px;
        }
        .footer p {
            margin-top: 15px;
            font-size: 18px;            
        }
    </style>
</head>
<body>
    <div class="container p-0">
        <div class="row justify-content-center logo">
            <div>
                <img src="{{ asset('asset/img/logo.png') }}" alt="Logo">
            </div>
            <div class="text-center p-2 bg-light">
                <h1>Stock Products</h1>
            </div>
        </div>
        <div class="card border-none">
            <div class="card-body table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>SR#</th>
                            <th>Id</th>
                            <th>School</th>
                            <th>Product</th>
                            <th>Size</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lowStockItems as $index => $stock)
                            @if ($stock->quantity < 10)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $stock->id }}</td>
                                    <td>{{ $stock->school->name }}</td>
                                    <td>{{ $stock->product->name }}</td>
                                    <td>{{ $stock->size->size }}</td>
                                    <td class="text-danger font-weight-bold">{{ $stock->quantity }}</td>
                                </tr>
                            @endif
                            @empty
                                <tr><td colspan="5" class="text-center">No low stock products.</td></tr>
                        @endforelse
                   </tbody>
                </table>
            </div>
        </div>
        <footer class="footer">
            <div class="row">
                <div class="col-md-6">
                    <p>2024 Best Uniform. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p>
                        <a href="#" style="color: rgb(223, 197, 148)">Home</a> |
                        <a href="#" style="color: rgb(223, 197, 148)">Terms of Service</a>
                    </p>
                </div>
            </div>
        </footer>        
    </div>
</body>
</html>

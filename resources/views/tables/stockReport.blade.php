<!doctype html>
<html lang="en">
  <head>
    <title>Stock Report</title>
    <!-- Required meta tags -->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- Bootstrap CSS -->
    <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> -->
    <link href="{{ asset('asset/css/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        .table td, .table th{
           vertical-align: middle
        }
        .logo{
            width: 20%;
            flex-shrink: 0;
        }
        .logo img{
            width: 100%;
            background-color: #1d1d50;
        }
        .heading {
            flex-grow: 1; /* Allow heading to grow and take remaining space */
        }
        .heading h1 {
            margin-bottom: 0; /* Remove margin-bottom for h1 */
        }
        body { margin: 20px; }
        @page { margin: 0px; }
    </style>
  </head>
<body>
    
    <div class="container">
        <div class="card card-primary">
            <div class="card-header d-flex">
                <div class="logo justify-content-start">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($imagePath)) }}" alt="Logo">
                </div>
                <div class="heading flex-grow-1 " >
                    <h1>Best Uniform</h1>
                    <h5 class="lh-base">Shop #1,Iqbal Stadium, Faisalabad<br>Phone: 0320-7755990</h5>
                </div>
            </div>
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
                        <tbody>
                            @foreach($data as $item)
                            <tr>
                                <td>{{ $item['school'] }}</td>
                                <td>{{ $item['product'] }}</td>
                                <td>{{ $item['size'] }}</td>
                                <td>{{ $item['quantity'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>        
        </div>
    </div>
</body>
</html>
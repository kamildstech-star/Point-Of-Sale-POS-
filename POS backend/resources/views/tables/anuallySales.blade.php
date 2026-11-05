<!doctype html>
<html lang="en">
  <head>
    <title>Sale Report</title>
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
        }
        .logo img{
            width: 100%;
            background-color: #1d1d50;
        }
        body { margin: 20px; }
        @page { margin: 0px; }
    </style>
  </head>
<body>
    <div class="container">
        <div class="card card-primary saletable">
            <div class="card-header d-flex">
                <div class="logo justify-content-start">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($imagePath)) }}" alt="Logo">
                </div>
                <div class="heading justify-content-end" >
                    <h1>Best Uniform</h1>
                    <h5 class="lh-base">Shop #1,Iqbal Stadium, Faisalabad<br>Phone: 0320-7755990</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!empty($data['annuallySales']))
                                @foreach($data['annuallySales'] as $year => $total)
                                    @php
                                        $date = new DateTime($year.'-01-01'); // Assuming $yearMonth is in format 'Y-m'
                                    @endphp
                                    <tr>
                                        <td>{{ $date->format('Y') }}</td>
                                        <td>{{ number_format($total) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3">No data available</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>        
            <div class="card-footer text-right">
                <label class="col-form-label">Total Sale: {{ number_format( $totalSale ) }}</label>
            </div>
        </div>
    </div>
</body>
</html>
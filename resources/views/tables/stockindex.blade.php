@extends('layouts.master')

@section('title')
Best Uniform | Stock Index
@endsection
@section('css')   
		<link href="{{ asset('asset/css/stockIndex.css') }}" rel="stylesheet">
@endsection
@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Stocks</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Stocks</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            @if(session()->has('message'))
                <div class="alert alert-primary">
                    {{ session()->get('message') }}
                </div>
            @endif
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body d-flex" style="background-color: #1d1d50">
                            <div class="col-md-9">
                                <a href="{{ route('stock.show')}}" class="btn" style="background-color: rgb(223, 197, 148);font-weight:600">Add Stock</a>
                            </div>
                            <div class="col-md-3 d-flex search-area">
                                <form action="" >
                                <div class="wrapper d-flex" >
                                            <input name="search"  class="search form-control order-id py-1" placeholder="Search" value="{{ $search }}">
                                            <button type="submit"  >
                                                <i class='fas fa-search order-check-btn'></i>
                                            </button>
                                        </div>
                                    </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Quantity</th>
                                        <th>School</th>
                                        <th>Product</th>
                                        <th>Size</th>
                                        <th>Base Price</th>
                                        <th>Sale Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stocks as $stock)
                                        <tr>
                                            <td>{{ $stock->quantity }}</td>
                                            <td>{{ $stock->school->name}}</td>
                                            <td>{{ $stock->product->name }}</td>
                                            <td>{{ $stock->size->size }}</td>
                                            <td>{{ $stock->base_price }}</td>
                                            <td>{{ $stock->sale_price }}</td>
                                            <td>
                                                <a href="{{ route('stock.edit' , ['id' => $stock->id]) }}" class="btn btn-success btn-sm">Edit</a>
                                                <a href="{{ route('stock.destroy' , ['id' => $stock->id]) }}" class="btn btn-danger btn-sm delete" onclick="return confirm('Are you sure you want to delete this? ')">Delete</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div>
                                {{ $stocks->links() }} <!-- Pagination links -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@extends('layouts.master')

@section('title')
Best Uniform | Return Index
@endsection
@section('css')   
    <link href="{{ asset('asset/css/returnIndex.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Returns</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Returns</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                 
                    <div class="card-body d-flex" style="background-color: #1d1d50">
                        <div class="col-md-9 ">
                            <a href="{{ route('return.show')}}" class="btn" style="background-color: rgb(223, 197, 148);font-weight:600">Return Order</a>
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
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>School</th>
                                    <th>Cutomer Name</th>
                                    <th>Phone</th>
                                    <th>Discount</th>
                                    <th>SubTotal</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td>{{ $order->school->name ? $order->school->name : ''}}</td>
                                        <td>{{ $order->user->name ? $order->user->name : '' }}</td>
                                        <td>{{ $order->phone}}</td>
                                        <td>{{ $order->discount}}</td>
                                        <td>{{ $order->subtotal}}</td>
                                        <td>{{ $order->total}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="ml-3"> 
                            {{ $orders->links() }} <!-- Pagination links -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
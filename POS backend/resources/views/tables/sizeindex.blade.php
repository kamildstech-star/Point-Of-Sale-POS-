@extends('layouts.master')

@section("title")
    Sizes Index 
@endsection
@section('css')   
    <link href="{{ asset('asset/css/sizeIndex.css') }}" rel="stylesheet">
@endsection

@section('content')

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Sizes</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Sizes</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        <div class="container">
            <div class="card">
                <div class="row">
                    <div class="col-md-12">
                                       <div class="card-body d-flex" style="background-color: #1d1d50">
                        <div class="col-md-9">
                            <a href="{{ route('size.show')}}" class="btn" style="background-color: rgb(223, 197, 148);font-weight:600">Add Size</a>
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
                                <th>Product</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr> 
                        </thead>
                        <tbody>
                            @foreach ($sizes as $size )
                                <tr>
                                    <td>{{ $size->product->name }}</td>
                                    <td>{{ $size->size }}</td>
                                    <td>
                                        <a href="{{ route('size.edit', ['id' => $size->id]) }}" class="btn btn-success btn-sm">Edit</a>
                                        <a href="{{ route('size.destory', ['id' => $size->id]) }}" class="btn btn-danger btn-sm delete" onclick="return confirm('Are you sure you want to delete this ? ')">Delete</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="ml-3">
                       {{ $sizes->links() }}
                    </div>
                </div><!-- /.card-body -->

                    </div>
                </div>
 
            </div><!-- /.card -->
        </div>
    </div>
@endsection


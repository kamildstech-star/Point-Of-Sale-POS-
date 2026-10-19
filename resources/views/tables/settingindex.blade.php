@extends('layouts.master')

@section('title')
Best Uniform | Setting Index
@endsection
@section('css')   
	<link href="{{ asset('asset/css/settingIndex.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Settings</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Settings</li>
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
                       
                        <div class="card-body d-flex" >
                            <div class="col-md-9">
                                <a href="{{ route('setting.show')}}" class="btn" style="background-color: rgb(223, 197, 148);font-weight:600">Add Settings</a>
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
                                        <th>Id</th>
                                        <th>key</th>
                                        <th>Value</th>
                                        <th>Operations</th>
                                    </tr> 
                                </thead>
                                <tbody>
                                    @foreach ($settings as $setting )
                                        <tr>
                                            <td>{{ $setting->id }}</td>
                                            <td>{{ $setting->key }}</td>
                                            <td>{{ $setting->value }}</td>
                                            <td>
                                                <a href="{{ route('setting.edit', ['id' => $setting->id]) }}" class="btn btn-success btn-sm">Edit</a>
                                                <a href="{{ route('setting.destroy', ['id' => $setting->id]) }}" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this Role? ')">Delete</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="ml-4">
                                {{ $settings->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>  
    </div>
@endsection
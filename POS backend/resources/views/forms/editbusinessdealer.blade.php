@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/editSettingIndex.css') }}" rel="stylesheet">
@endsection


@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Edit Business Dealer</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit Business Dealer</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Business Dealer</h3>
                </div>
                <form action="{{ route('businessDealer.update', ['id' => $businessDealer->id]) }}" method="POST">
                    @csrf
                    <div class="card-body"> 
                    <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" name="name" class="form-control" id="name" placeholder="Name" value="{{ $businessDealer->name }}">
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>                
                        <div class="form-group">
                            <label for="type">Type:</label>
                            <select name="type" class="form-control school">
                                <option value="">Select Type</option>
                                @foreach ($type as $key => $t)
                                    <option value="{{ $t }}" {{ $businessDealer->type  == $t ? 'selected' : '' }}>
                                        {{ $key }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>                    
                               
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="" name="address" class="form-control" id="address" placeholder="Address" value="{{ $businessDealer->address }}">
                            @error('address')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>                           
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="phone" name="phone" class="form-control" id="phone" placeholder="Phone" value="{{ $businessDealer->phone_number }}">
                            @error('phone')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>     
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" name="description" class="form-control" id="description" placeholder="Description" value="{{ $businessDealer->description }}">
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>                             
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
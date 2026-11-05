@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/editExpences.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Edit Expenses</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit Expenses</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mt-2">Edit Expenses</h3>
                </div>
                <form action="{{ route('expenses.update', ['id' => $expenses->id]) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" class="form-control" id="name" placeholder="Name" value="{{ $expenses->name }}">
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>            
                        <div class="form-group">
                            <label for="name">Description</label>
                            <textarea class="form-control" name="description" id="description" cols="30" rows="5">{{ old('description', $expenses->description ?? '') }}</textarea>
                            @error('description')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select class="form-control" name="type">
                                <option value="0" {{ $expenses->type == 0 ? 'selected' : '' }}>1000</option>
                                <option value="1" {{ $expenses->type == 1 ? 'selected' : '' }}>2000</option>
                                <option value="2" {{ $expenses->type == 2 ? 'selected' : '' }}>3000</option>
                            </select>
                            @error('type')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="name">Amount</label>
                            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount" value="{{ $expenses->amount }}">
                            @error('amount')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn" >Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

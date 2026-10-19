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
                        <h1 class="m-0 ">Edit Settings</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit Setting</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Settings</h3>
                </div>
                <form action="{{ route('setting.update', ['id' => $settings->id]) }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="key">Key</label>
                            <input type="text" name="key" class="form-control" id="key" placeholder="Key" value="{{ $settings->key }}">
                        </div>            
                        <div class="form-group">
                            <label for="value">Value</label>
                            <input type="text" name="value" class="form-control" id="value" placeholder="Value" value="{{ $settings->value }}">
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
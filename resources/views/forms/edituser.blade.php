@extends('layouts.master')
@section('css')   
	<link href="{{ asset('asset/css/editUser.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Edit User</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Edit User</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <div class="container">
            <div class="card ">
                <div class="card-header" >
                    <h3 class="card-title mt-2">Edit User</h3>
                </div>
                <form action="{{ route('user.update' ,['id' => $users->id]) }}" method="POST" id="edituser">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Name" value="{{ $users->name }}">
                                @error('name')
                                    <div class="text-danger">{{ $message }}</div>                                
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="email">Email address</label>
                                <input type="email" name="email" class="form-control" placeholder="Email" value="{{ $users->email }}">
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>                                
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Password">
                                @error('password')
                                    <div class="text-danger">{{ $message }}</div>                                
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="password">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password">
                                @error('password')
                                    <div class="text-danger">{{ $message }}</div>                                
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select name="role" id="role" class="form-control">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" {{ $users->role_id == $role->id ? 'selected' : '' }}>
                                            {{ $role->role }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="text-danger">{{ $message }}</div>                                
                                @enderror
                            </div>
                            
                        </div><!-- /.card-body -->
                        <div class="card-footer">
                            <button type="submit" class="btn" >Update</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function(){
            $('#role').select2({
                placeholder: "Select School",
                theme: "bootstrap4", // Optional: Change the theme to match Bootstrap 4
                dropdownSearch: true // Enable searching within dropdown
            });
        })
    </script>
@endsection

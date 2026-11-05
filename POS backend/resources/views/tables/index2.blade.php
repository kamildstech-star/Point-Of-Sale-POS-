@extends('layouts.master')

@section('title')
Best Uniform | Roles Index
@endsection
@section('css')   
    <link href="{{ asset('asset/css/roleIndex.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 ">Roles</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active">Roles</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
          <div class="modal fade" id="exampleModal2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add Permission</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <form action="{{ route('role.permission') }}" method="POST">
                     @csrf
                     <input type="hidden" class="roles_id" id="roles_id" name="roles_id" >
                    <div class="modal-body">
                        @foreach ($permission as $perm )
                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="permission_{{$perm->id}}" name="permission[{{$perm->id}}]" >
                                <label class="form-check-label" for="{{$perm->id}}">{{$perm->name}}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" >Submit</button>
                    </div>
              </form>

            </div>
          </di>
        </div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body d-flex" style="background-color: #1d1d50">
                            <div class="col-md-9">
                                <a href="{{ route('roles.show')}}" class="btn" style="background-color: rgb(223, 197, 148);font-weight:600">Add Role</a>
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
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr> 
                                </thead>
                                <tbody>
                                    @foreach ($roles as $role )
                                        <tr>
                                            <td>{{ $role->id }}</td>
                                            <td>{{ $role->role }}</td>
                                            <td>
                                                <a href="{{ route('roles.edit', ['id' => $role->id]) }}" class="btn btn-success btn-sm">Edit</a>
                                                <a href="{{ route('roles.destroy', ['id' => $role->id]) }}" class="btn btn-danger btn-sm delete" onclick="return confirm('Are you sure you want to delete this ? ')">Delete</a>
                                                <a href="" class="btn btn-warning sm-btn add permission-model-trigger" style="padding: 3px 10px;" data-toggle="modal" data-target="#exampleModal2" permission-id="{{$role->id}}">Permission</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="ml-4">
                                {{ $roles->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>  
    </div>
@endsection
@section('script')
    <script>

        $(document).ready(function (event) {
            $('body').on('click','.permission-model-trigger', function (e) {
                var permission_id = $(this).attr('permission-id');
                resetPermissionInModal(permission_id);
            });
        });
        function resetPermissionInModal(id){
            $('#roles_id').val(id);
            var elem = $('.form-check-input').each(function () {
                $(this).prop('checked',false);
            });
            
            $.ajax({
                url: `/getPermission/${id}`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        response.permissions.forEach((item) => {
                            $('#permission_'+item).prop('checked', true);
                        });
                    }
                },
                error: function(xhr) {

                }
            });
        }
        
    </script>
@endsection

<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use Illuminate\Http\Request;
use Session;

class PermissionController extends Controller
{
    public function show(){
        return view('forms.permission');
    }

    public function createpermission( Request $request){
        $permission= Permissions::where([    
            ['name', '=', $request->input('permission')],
        ])->first();

        if($permission) {
                Session::flash('error', 'Product already exists');
                return redirect()->back();
        }
        $request->validate([
            'permission' => 'required|string'
            ]);
        $permissions = new Permissions();
        $permissions->name = $request->input('permission');
        $permissions->save();

        Session::flash('success', 'Permission Added Successfully');
        return redirect()->route('permission.index');
    }

    public function permissionindex(Request $request){
        $search = $request->input('search', '');
        if(!empty($search)){
            $permissions = Permissions::where('id', $search)->orWhere('name', 'like', '%' . $search . '%')->paginate(10);
        } else{
            $permissions = Permissions::paginate(10);
        }
        return view('tables.permissionindex', ['permissions' => $permissions, 'search' => $search]);
    }
    public function editpermission($id){
        $permissions = Permissions::find($id);
        return view('forms.editpermission', ['permissions' => $permissions]);
    }
    public function updatepermission(Request $request,$id){
        $request->validate([
            'permission' => 'required|string'
        ]);
        $permissions = Permissions::find($id);
        $permissions->name = $request->input('permission');
        $permissions->save();

        Session::flash('success', 'Permission Updated Successfully');
        return redirect()->route('permission.index');
    }
    public function deletepermission($id){
        $permission = Permissions::find($id);
        $permission->delete();

        Session::flash('success', 'Permission Deleted Successfully');
        return redirect()->route('permission.index');
    }
}

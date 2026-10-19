<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use App\Models\User;
use App\Models\Roles;
use App\Models\Sizes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use PhpParser\Node\Stmt\Return_;

class AdminController extends Controller
{
    public function show(){
        return view('forms.roles');
    }
    public function create(Request $request){
        $request->validate([
            'role' => 'required|string|max:255',
        ]);
        $roles = new Roles();
        $roles->role=$request->role;
        $roles->save();

        Session::flash('success', 'Role Created Successfully');
        return redirect()->route('roles.index');
    }
    public function index(Request $request){
        $search = $request->input('search', '');
        if(!empty($search)) {
            $roles = Roles::where('id', $search)->orWhere('role', 'like' , '%' . $search . "%")->paginate(10);
        } else{
            $roles = Roles::paginate(10);
        }
        $permission = Permissions::all();
        return view('tables.index2', ['roles' => $roles, 'permission'=>$permission, 'search' => $search]);
    }
    public function edit($id){
        $roles = Roles::find($id);
        return view('forms.update', ['roles' => $roles]);
    }
    public function update(Request $request, $id){
        $request->validate([
            'role' => 'required|string|max:255' 
        ]);
        $roles = Roles::find($id);
        $roles->role = $request->input('role');
        $roles->save();

        Session::flash('message', 'Role Updated Successfully');
        return redirect()->route('roles.index');
    }
    public function destroy($id){
        $roles = Roles::find($id);        
        $roles->delete();

        Session::flash('success', 'Role Deleted Successfully');
        return redirect()->route('roles.index');
    }
    public function rolePermission(Request $request){
        $permission = $request->permission;
        if($permission){
            $y = array_keys($permission);
            $permission = json_encode($y);
        }else{
            $permission = null;
        }
        $roles = Roles::find($request->roles_id);
        $roles->permission = $permission;
        $roles->save();
        Session::flash('success', 'Permission Added Successfully');
        return redirect()->route('roles.index');
    }
    public function getPermission($id){ 
        $Role = Roles::find( $id);
        $permissions = $Role->permission ?? '[]';
        $permissions = json_decode($permissions);
        if (count($permissions) > 0) {
            return response()->json(['success' => true, 'permissions' => $permissions, 'id'=> $id], 200);
        } else{
            return response()->json(['success' => false], 404);
        }
    }
}

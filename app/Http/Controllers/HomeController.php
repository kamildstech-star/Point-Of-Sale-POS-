<?php
namespace App\Http\Controllers;
use App\Models\Roles;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(){
        if (auth()->user()->role_id !=1) {
            return redirect()->route('login');
        }
        $roles = Roles::all();
        return view('users.create', ['roles' => $roles]);
    }
    public function create(Request $request){
        if (auth()->user()->role_id !=1) {
            return redirect()->route('login');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'min:6|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'min:6',
            'role' => 'required|integer|exists:roles,id',
        ]);
        $users = new User();
        $users->name = $request->input('name');
        $users->email = $request->input('email');
        $users->password = $request->input('password');
        $users->role_id = $request->input('role');
        $users->save();

        Session::flash('success', 'User Created Successfully');
        return redirect()->route('users.index');
    }
    public function index(Request $request){
        $users = User::with('role')->get();
        $search = $request->input('search', '');
        if (!empty($search)) {
            $users = User::where('id', $search)->orWhere('name', 'like', '%' . $search . '%')->paginate(10);
        } else {
            $users = User::paginate(10);
        }
        return view('users.index', ['users' => $users, 'search' => $search]);
    }
    public function destroy($id){
        $users = User::find($id);
        $users->delete();

        Session::flash('success', 'User Deleted Successfully');
        return redirect()->route('users.index');
    }
    public function edit($id){
        $roles = Roles::all();
        $users = User::find($id);

        return view('forms.edituser', ['users' => $users , 'roles' => $roles]);
    }
    public function update(Request $request, $id){
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'min:6|nullable|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'nullable|min:6',
            'role' => 'required|integer|exists:roles,id',
        ]);
        $users = User::find($id);
        $users->name = $request->name;
        $users->email = $request->email;
        if (!empty($request->password)) {
            $users->password = $request->password;
        }
        
        $users->role_id = $request->role;
        $users->save();

        Session::flash('success', 'User Update Successfully');
        return redirect()->route('users.index');
    }
}

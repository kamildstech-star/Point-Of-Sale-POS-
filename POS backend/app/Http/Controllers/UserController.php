<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Roles;
use App\Models\Stocks;
use App\Models\Todolist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Event\ViewEvent;

class UserController extends Controller
{ 
    public function dashboard(){
        $totalOrders = Order::count();
        $totalQuantity = Stocks::sum('quantity');
        $orders = Order::select(DB::raw('DATE_FORMAT(created_at, "%M") as month'), DB::raw('count(*) as order_count'))
                        ->groupBy('month')
                        ->orderBy('created_at')
                        ->get();
        $dailySale  = Order::whereDate('created_at', Carbon::today()->toDateTimeString())->pluck('total')->sum();
        // Format the data for the chart
        $labels = $orders->pluck('month')->toArray();
        $data = $orders->pluck('order_count')->toArray();
        $salesChart = collect([
            'month' => $labels,
            'data' => $data
        ]);
        $orders = Order::latest()->take(7)->get();
        $todoList = Todolist::paginate(7);
        return view('index', compact('totalOrders', 'totalQuantity', 'salesChart', 'todoList', 'orders', 'dailySale'));
    }
    public function todolist(Request $request){
        $todoList = new Todolist();
        $todoList->completed = false;
        $todoList->description = $request->input('description');
        $todoList->save(); 
        return response()->json( ['success' => true]);
    }
    public function getTasks() {
        $todoList = Todolist::all();
        return response()->json(['success' => true , 'todoList' => $todoList]); 
    }

    public function updateStatus(Request $request){

        $todoList = Todolist::find($request->id);
        if($todoList) {
            $todoList->completed = $request->completed;
            $todoList->save();
            return response()->json(['success' => true, 'success' => 'Task updated successfully']);
        }
        return response()->json(['success' => false, 'message' => 'Task not found']);
    }
    
    public function updateTask(Request $request,$id){
        $todoList = Todolist::find($id);
        if ($todoList) {
            $todoList->description = $request->input('description');
            $todoList->completed = false;
            $todoList->save();
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }
    }
    public function login(){
        return view('auth.login');
    }
    public function loginpost(Request $request){
        $credentials = $request->validate([
            'email' => 'required | email',
            'password' => 'required',
        ]);
        if (Auth::attempt($credentials)) {
            return redirect()->route('dashboard')->with('success', 'Login Successfully');
        } else {
            if (!User::where('email', $request->email)->exists()) {
                return back()->withErrors(['email' => 'The provided email is not Registered.'])->withInput();
            } else {                
                return back()->withErrors(['password' => 'The provided password is Incorrect.'])->withInput();
            }
        }
    }
    public function logoutpost(){
        Auth::logout();
        return redirect()->route('login');
    }
}

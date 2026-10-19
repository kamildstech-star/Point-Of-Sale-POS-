<?php

namespace App\Http\Controllers;

use App\Models\Expenses;
use FontLib\Table\Type\name;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ExpensesController extends Controller
{
    public function show(){
        return view('forms.expenses');
    }
    public function create(Request $request){
        $request->validate([
            'name' => 'required',
        ]);
        $expenses = new Expenses();
        $expenses->name = $request->input('name');
        $expenses->description = $request->input('description');
        $expenses->type = $request->input('type');
        $expenses->amount = $request->input('amount');
        $expenses->save();

        Session::flash('success', 'Expenses Added Successfully');
        return redirect()->route('expenses.index');        
    }
    public function index(Request $request){
        $search = $request->input('search', '');
        if(!empty($search)){
            $expenses = Expenses::where('id', $search)->orWhere('name', 'like', '%' . $search . '%')->paginate(10);
        } else{
            $expenses = Expenses::paginate(10);
        }
        return view('tables.expenseesIndex', ['expenses' => $expenses, 'search' => $search]);
    }
    public function edit($id){
        $expenses = Expenses::find($id);
        return view('forms.editexpenses', ['expenses' => $expenses]);
    }
    public function update(Request $request, $id){
        $expenses = Expenses::find($id);
        $expenses->name = $request->input('name');
        $expenses->description = $request->input('description');
        $expenses->type = $request->input('type');
        $expenses->amount = $request->input('amount');
        $expenses->save();

        Session::flash('success', 'Expenses Updated Successfully');
        return redirect()->route('expenses.index');
    }
    public function destroy($id){
        $expenses = Expenses::find($id);
        $expenses->delete();

        Session::flash('success', 'Expenses Deleted Successfully');
        return redirect()->route('expenses.index');
    }
}

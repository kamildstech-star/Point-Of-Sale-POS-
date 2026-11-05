<?php

namespace App\Http\Controllers;

use App\Models\Schools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SchoolController extends Controller
{
    public function showschool(){
        return view('forms.schools');
    }
    public function createschool(Request $request){
        $school= Schools::where([    
            ['name', '=', $request->input('name')],
        ])->first();
        if($school) {
                Session::flash('error', 'School already exists');
                return redirect()->back();
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
        ]);
        $schools = new Schools();
        $schools->name = $request->name;
        $schools->address = $request->address;
        $schools->percentage = $request->percentage;
        $schools->fixed_price = 0;
        $schools->margin_type = 0;
        $schools->save();
       
        Session::flash('success', 'School Added Successfully');
        return redirect()->route('school.index');
    }
    public function schoolindex(Request $request){
        $search = $request->input('search', '');
        if(!empty($search)){
            $schools = Schools::where('id', $search)->orWhere('name' , 'like' , '%' . $search . '%')->paginate(10);
        } else{
            $schools = Schools::paginate(10);
        }
        return view('tables.schoolindex', ['schools' => $schools, 'search' => $search]);
    }
    public function editschool($id){
        $schools = Schools::find($id);
        return view('forms.editschool', ['schools' => $schools]);

    }
    public function updateschool(Request $request, $id){
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
        ]);
        $schools = Schools::find($id);
        $schools->name = $request->name;
        $schools->address = $request->address;
        $schools->percentage = $request->percentage;
        $schools->save();

        Session::flash('success', 'School Updated Successfully');
        return redirect()->route('school.index');
    }
    public function destroy($id){
        $schools = Schools::find($id);
        $schools->delete();

        Session::flash('success', 'School Deleted Successfully');
        return redirect()->route('school.index');
    }
}


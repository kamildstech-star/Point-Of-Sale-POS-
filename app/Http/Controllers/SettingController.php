<?php

namespace App\Http\Controllers;

use App\Models\Expenses;
use App\Models\settings;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class settingController extends Controller
{
    public function showsetting(){
        return view('forms.setting');
    }
    public function createsetting(Request $request){
        $request->validate([
            'key' => 'required|unique:settings,key',
            'value' => 'required',
        ]);
        $settings = new settings();
        $settings->key = $request->input('key');
        $settings->value = $request->input('value');
        $settings->save();

        Session::flash('success', 'Setting Added Successfully');
        return redirect()->route('settings.index');
    }
    public function settingindex(Request $request){
        $search = $request->input('search', '');
        if(!empty($search)){
            $settings = Settings::where('key', 'like', '%' . $search . '%')->paginate(10);
        } else{
            $settings = Settings::paginate(10);
        }
        return view('tables.settingindex', [ 'settings' => $settings, 'search' => $search]);
    }
    public function editsetting($id){
        $settings = settings::find($id);
        return view('forms.editsetting', ['settings' => $settings]);
    }
    public function updatesetting(Request $request, $id){    
        $settings = Settings::find($id);    
        $settings->key = $request->input('key');
        $settings->value = $request->input('value');
        $settings->save();

        Session::flash('success', 'Setting Updated Successfully');
        return redirect()->route('settings.index');
    }
    public function deleteSetting($id){
        $settings = Settings::find($id);
        $settings->delete();

        Session::flash('success', 'Setting Deleted Successfully');
        return redirect()->route('settings.index');
    }
}

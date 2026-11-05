<?php

namespace App\Http\Controllers;

use App\Models\Sizes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Product;

class SizeController extends Controller
{
    public function sizes(){
        $products = product::all();
        return view('forms.sizes', ['products' => $products]);
    }
    public function create(Request $request){

        $size= Sizes::where([    
            ['product_id', '=', $request->input('product_id')],
            ['size', '=', $request->input('size')]
        ])->first();

        if($size) {
            Session::flash('error', 'Product size already exists');
            return redirect()->back();
        }
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'size' =>  'required',
        ]);
        $sizes = new Sizes();
        $sizes->size = $request->input('size');
        $sizes->product_id = $request->input('product_id');
        $sizes->save();
    
        Session::flash('success', 'Size Added Successfully');
        return redirect()->route('sizes.index');
    }
    
    public function index(Request $request){
        $search = $request->input('search', '');
        if(!empty($search)) {
            $sizes = Sizes::where('id', $search)->orWhere('size', 'like' , '%' . $search . '%')->orderBy('id', 'DESC')->paginate(10);
        } else{
            $sizes = Sizes::orderBy('id', 'DESC')->paginate(10);
        }
        return view('tables.sizeindex', ['sizes' => $sizes, 'search' => $search]);
    }
    public function destory($id){
        $sizes = Sizes::find($id);
        $sizes->delete();

        Session::flash('success', 'Size Deleted Successfully');
        return redirect()->route('sizes.index');
    }
    public function editsize($id){
        $products = Product::all();
        $sizes = Sizes::find($id);
        return view('forms.sizeupdate', ['sizes' => $sizes, 'products' => $products]);
    }
    public function updatesize(Request $request,$id){
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'size' =>  'required',
        ]);
        $sizes = Sizes::find($id);
        $sizes->size = $request->input('size');
        $sizes->product_id = $request->input('product_id');
        $sizes->save();

        Session::flash('success', 'Size Updated Successfully');
        return redirect()->route('sizes.index'); 
    }
}

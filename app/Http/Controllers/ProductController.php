<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ProductController extends Controller
{
    public function showproduct(){
        return view('forms.product');
    }
    public function createproduct( Request $request){
        $product= Product::where([    
            ['name', '=', $request->input('product')],
        ])->first();

        if($product) {
                Session::flash('error', 'Product already exists');
                return redirect()->back();
        }
        $request->validate([
            'product' => 'required|string'
            ]);
        $products = new Product();
        $products->name = $request->input('product');
        $products->save();

        Session::flash('success', 'Product Added Successfully');
        return redirect()->route('product.index');
    }

    public function productindex(Request $request){
        $search = $request->input('search', '');
        if(!empty($search)){
            $products = Product::where('id', $search)->orWhere('name', 'like', '%' . $search . '%')->orderBy('id', 'DESC')->paginate(10);
        } else{
            $products = Product::orderBy('id', 'DESC')->paginate(10);
        }
        return view('tables.productindex', ['products' => $products, 'search' => $search]);
    }
    public function deleteProduct($id){
        $product = Product::find($id);
        $product->delete();

        Session::flash('success', 'Product Deleted Successfully');
        return redirect()->route('product.index');
    }
    public function editproduct($id){
        $products = Product::find($id);
        return view('forms.editproduct', ['products' => $products]);
    }
    public function updateproduct(Request $request,$id){
        $request->validate([
            'product' => 'required|string'
        ]);
        $products = Product::find($id);
        $products->name = $request->input('product');
        $products->save();

        Session::flash('success', 'Product Updated Successfully');
        return redirect()->route('product.index');
    }
}

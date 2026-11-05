<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use App\Models\Product;
use App\Models\Schools;
use App\Models\Sizes;
use App\Models\Stocks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Constants\AppConst;

use function Laravel\Prompts\search;

class StockController extends Controller
{
    public function showstock(){
        $sizes = Sizes::all();
        $products = Product::all();
        $schools = Schools::all();

        return view('forms.stocks', [
            'sizes' => $sizes,
            'products' => $products,
            'schools' => $schools,
        ]);
    }
    public function createstock(Request $request){

        $stock = Stocks::where([    
            ['school_id', '=', $request->input('school')],
            ['product_id', '=', $request->input('product')],
            ['size_id', '=', $request->input('size')]
        ])->first();

        if($stock) {
            Session::flash('error', 'Stock already exists');
            return redirect()->back();
        }
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'school' => 'required|exists:schools,id',
            'product' => 'required|exists:products,id',
            'size' => 'required|exists:sizes,id',
            'baseprice' => 'required|numeric|min:0',
            'saleprice' => 'required|numeric|min:0',
        ]);
        $quantity = max(1, $request->input('quantity'));
        $stocks = new Stocks();
        $stocks->quantity = $quantity;
        $stocks->school_id = $request->input('school');
        $stocks->product_id	= $request->input('product');
        $stocks->size_id = $request->input('size');        
     
        // Generate a unique 8-character product code using md5
        $rawString = $stocks->school_id . $stocks->product_id . $stocks->size_id;
        $stocks->product_code = substr(md5($rawString), 0, 10); // Limit to 8 characters

        $stocks->base_price = $request->input('baseprice');
        $stocks->sale_price = $request->input('saleprice');
        $stocks->save();
        
        $this->logAction('stock', 'Added stock', AppConst::LOGS_TYPE['create'], $stocks);
        Session::flash('success', 'Stock Added Successfully');
        return redirect()->route('stocks.index');
    }
    public function stockindex(Request $request){
        $search = $request->input('search', '');
        $stocks = Stocks::with(['size', 'product', 'school']);
        if (!empty($search)) {
            $stocks = Stocks::whereHas('school', function($query) use ($search) {
                $query->where('id', $search)->orWhere('quantity', 'like', '%' . $search . '%')
                                            ->orWhere('name', 'like', '%' . $search . '%')
                                            ->orderBy('id', 'DESC');
            })->paginate(10);
        } else {
            $stocks = Stocks::orderBy('id', 'DESC')->paginate(10);
        }
        return view('tables.stockindex', [
            'stocks' => $stocks,
            'search' => $search, 
        ]);
    }
    public function editstock($id){
        $sizes = Sizes::all();
        $products = Product::all();
        $schools = Schools::all();
        $stocks = Stocks::find($id);
    
        return view('forms.editstock', [
            'sizes' => $sizes,
            'products' => $products,
            'schools' => $schools,
            'stocks' => $stocks,
        ]);
    }
    public function updatestock(Request $request,$id){
        $request->validate([
            'quantity' => 'integer',
            'school' => 'required|exists:schools,id',
            'product' => 'required|exists:products,id',
            'size' => 'required|exists:sizes,id',
            'baseprice' => 'required|numeric|min:0',
            'saleprice' => 'required|numeric|min:0',
        ]);
        $stocks = Stocks::findorfail($id);
        $stocks->quantity = $request->input('quantity');
        $stocks->school_id = $request->input('school');
        $stocks->product_id	= $request->input('product');
        $stocks->size_id = $request->input('size');
        $stocks->base_price = $request->input('baseprice');
        $stocks->sale_price = $request->input('saleprice');
        $stocks->save();

        $this->logAction('stock', 'Updated stock', AppConst::LOGS_TYPE['update'], $stocks);

        Session::flash('success', 'Stock Updated Successfully');
        return redirect()->route('stocks.index');
    }
    public function destroystock($id){
        $stocks = Stocks::findorfail($id);

        $logData = clone $stocks;

        $stocks->delete();
        $this->logAction('stock', 'Deleted stock', AppConst::LOGS_TYPE['delete'], $logData);
        Session::flash('success', 'Stock Deleted Successfully');
        return redirect()->route('stocks.index');
    }
    protected function logAction($name, $description, $type, $stock) {
        $logs = new Logs();
        $logs->Name = $name;
        $logs->description = json_encode([
            'school' => $stock->school->name,
            'product' => $stock->product->name,
            'size' => $stock->size->size,
            'quantity' => $stock->quantity,
        ]);
        $logs->type = $type;  // Ensure $type is one of 'add', 'update', 'delete'
        $logs->save();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnItems;
use App\Models\ReturnOrder;
use App\Models\Schools;
use App\Models\Sizes;
use App\Models\Stocks;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\Xml\Totals;

use function Laravel\Prompts\alert;

class returnOrderController extends Controller
{
    public function returnorder(){
        return view('forms.returnorder');
    }
    public function getOrder($id){  
        $order = DB::table('orders')
                            ->join('schools as sch', 'orders.school_id', '=', 'sch.id')      // Join users and posts tables
                            ->join('users as u', 'orders.user_id', '=', 'u.id')      // Join users and posts tables
                            ->join('order_items', 'orders.id', '=', 'order_items.order_id')      // Join users and posts tables
                            ->join('schools as schl', 'order_items.school_id', '=', 'schl.id')  // Join posts and comments tables
                            ->join('sizes', 'order_items.size_id', '=', 'sizes.id')  // Join posts and comments tables
                            ->join('products', 'order_items.product_id', '=', 'products.id')  
                            ->join('stocks', function($join) {
                                $join->on('stocks.school_id', '=', 'order_items.school_id')   // Join users and posts on user_id
                                     ->on('stocks.product_id', '=', 'order_items.product_id') // Additional condition between posts and users
                                     ->on('stocks.size_id', '=', 'order_items.size_id'); // Additional condition between posts and users
                            })// Join posts and comments tables
                    ->where('orders.id', $id)  // Filter by user id
                    ->select('orders.id as order_id','order_items.quantity', 'order_items.total', 'order_items.price','order_items.price_after_discount','order_items.margin_after_discount', 'order_items.discount', 'order_items.returned','order_items.school_margin','order_items.id as order_item_id', 'sch.name as school','schl.name as oi_school', 'sizes.size', 'products.name as product','stocks.quantity as stock','u.name as customer_name','u.phone as phone')
                ->get();
        if ( count($order) == 0) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }
        else{
            return response()->json(['success' => true, 'order' => $order], 200);
        }
    
    }

    public function createreturn(Request $request){
        try {
            $request->validate([
                'items.*.return' => 'required|gt:0',

            ]);
    
            $items = $request->return;
            // print_r($items);
            // exit;
        
            foreach ($items as $key => $item) {
              
                $orderItem =   OrderItem::find($key);

                $returned = $orderItem->returned;
                $quantity = $orderItem->quantity;
                $return_added = $item;

                if(!(($quantity - $returned ) >= $return_added)){
                    return response()->json(['error' => 'Return is greater then purchase quantity.'], 422);



                }

                $orderItem->increment('returned', $item);
                
                   $school_id  =  $orderItem->school_id;
                   $product_id =  $orderItem->product_id;
                   $size_id    =  $orderItem->size_id;
                   $order_id   =  $orderItem->order_id;

                   Stocks::where('school_id', $school_id)
                            ->where('product_id', $product_id)
                            ->where('size_id', $size_id)
                            ->increment('quantity', $item);

                    Order::where('id', $order_id)->update(['is_return' => 1]);
                }
            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }    
    public function returnindex(Request $request){
   
        $orders = Order::with('school')->where('is_return', 1)->get()->all();
        $search = $request->input('search', '');

        if($orders){
            $orders = Order::with('school')->where('is_return', 1)->orderBy('id', 'DESC')->paginate(10);
        }
        if(!empty($search)) {
            $orders = Order::leftJoin('users as u', 'u.id', '=', 'orders.user_id')
                             ->select('u.id as u_id', 'orders.id as order_id', 'u.name as customer_name','u.phone as phone', 'orders.*')
                             ->whereHas('orderitems', function($query) use ($search) {
                                    $query->where('orders.id', $search )
                                          ->orWhere('u.name', 'like' , '%' . $search . '%')
                                          ->orWhere('u.phone', 'like' , '%' . $search . '%')
                                          ->orderBy('orders.id', 'DESC');
                             })->paginate(10);
        } else {
            $orders = Order::with('school')->with('user')->where('is_return', 1)->orderBy('orders.id', 'DESC')->paginate(10);
        }
        return view('tables.returnindex', ['orders' => $orders, 'search' => $search ]);
    }
    public function editreturn($id) {
        // Fetch specific order based on $id
        $order = Order::all();  
        $schools = Schools::all();
        $products = Product::all();
        $sizes = Sizes::all();
        $returns = ReturnOrder::with('returnitems')->find($id);
        return view('forms.editreturn', [
            "order" => $order,
            "schools" => $schools,
            "products" => $products,
            "sizes" => $sizes,
            "returns" => $returns,
        ]);
    }
    public function updatereturn(Request $request, $id) {
        try {
            $request->validate([
                'showsubtotal' => 'required',
                'showtotalprice' => 'required',
                'items.*.order-id' => 'required|exists:orders,id',
                'items.*.school' => 'required|exists:schools,id',
                'items.*.product' => 'required|exists:products,id',
                'items.*.size' => 'required',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric',
            ], [
                'showsubtotal' =>'Subtotal cannot be empty.',
                'showtotalprice' => 'Total cannot be empty.',
                'items.*.order-id.required' => 'Order ID is required.',
                'items.*.order-id.exists' => 'Order ID is invalid.',
                'items.*.school.required' => 'School selection is required.',
                'items.*.school.exists' => 'School selection is invalid.',
                'items.*.product.required' => 'Product selection is required.',
                'items.*.product.exists' => 'Product selection is invalid.',
                'items.*.size.required' => 'Size selection is required.',
                'items.*.quantity.required' => 'Quantity is required.',
                'items.*.quantity.integer' => 'Quantity must be an integer.',
                'items.*.quantity.min' => 'Quantity must be at least 1.',
                'items.*.price.required' => 'Price is required.',
                'items.*.price.numeric' => 'Price must be a numeric value.',
            ]);
            $returns = ReturnOrder::find($id);
            if (!$returns) {
                return redirect()->route('returns.index')->withErrors('No Returns found.');
            }
            DB::beginTransaction();
            // Update main Returns details
            $returns->subtotal = $request->input('showsubtotal');
            $returns->total = $request->input('showtotalprice');
            $returns->save();
            // Handle Return items
            $items = $request->input('items', []);
            $existingReturnItemsIds = $returns->returnitems->pluck('id')->toArray();
            foreach ($items as $key => $item) {
                $orderItem = OrderItem::where('order_id', $item['order-id'])
                                      ->where('product_id', $item['product'])
                                      ->first();
                if (!$orderItem) {
                    return response()->json([
                        'errors' => [
                            'items.' . $key . 'order-id' => 'Order item not found.'
                        ]
                    ], 422);
                }
                // Check if the return quantity exceeds the order item quantity
                if ($item['quantity'] > $orderItem->quantity) {
                    return response()->json([
                        'errors' => [
                            'items.' . $key . '.quantity' => 'Greater then perchased quantity'
                        ]
                    ], 422);
                }
                $stock = Stocks::where('school_id', $item['school'])
                               ->where('product_id', $item['product'])
                               ->where('size_id', $item['size'])
                               ->first();
                if (isset($item['id'])) {
                    // Existing return item
                    $returnItem = ReturnItems::find($item['id']);
                    if ($returnItem) {
                        // Calculate quantity difference
                        $quantityDifference = $item['quantity'] + $returnItem->quantity;
                        // Adjust stock quantity
                        $stock->quantity += $quantityDifference;
                        // Update return item details
                        $returnItem->order_id = $item['order-id'];
                        $returnItem->school_id = $item['school'];
                        $returnItem->product_id = $item['product'];
                        $returnItem->size_id = $item['size'];
                        $returnItem->order_item_id = $orderItem->id;
                        $returnItem->quantity = $item['quantity'];
                        $returnItem->price = $item['price'];
                        $returnItem->save();
                        // Remove from existing items list
                        $existingReturnItemsIds = array_diff($existingReturnItemsIds, [$item['id']]);
                    }
                } else {
                    // New return item
                    $returnItem = new ReturnItems();
                    $returnItem->return_id = $returns->id;
                    $returnItem->order_id = $item['order-id'];
                    $returnItem->school_id = $item['school'];
                    $returnItem->product_id = $item['product'];
                    $returnItem->size_id = $item['size'];
                    $returnItem->order_item_id = $orderItem->id;
                    $returnItem->quantity = $item['quantity'];
                    $returnItem->price = $item['price'];
                    $returnItem->save();
                    // Decrease stock for new return items
                    $stock->quantity += $item['quantity'];
                }
                // Save the updated stock
                $stock->save();
            }
            // Remove deleted return items and restore stock
            foreach ($existingReturnItemsIds as $itemId) {
                $returnItem = ReturnItems::find($itemId);
                if ($returnItem) {
                    // Find associated stock
                    $stock = Stocks::where('school_id', $returnItem->school_id)
                                   ->where('product_id', $returnItem->product_id)
                                   ->where('size_id', $returnItem->size_id)
                                   ->first();
                    if ($stock) {
                        // Restore stock quantity
                        $stock->quantity -= $returnItem->quantity;
                        $stock->save();
                    }
                    // Delete return item
                    $returnItem->delete();
                }
            }
            DB::commit();
            session()->flash('success', 'Order Updated Successfully');
            return response()->json(['success' => true, 'return_id' => $returns->id]);
    
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
    
    public function deletereturn($id){
        $returns = ReturnOrder::find($id);
        if($returns) {
            $returns->returnitems()->delete();
            $returns->delete();
        }
        
        return redirect()->route('returns.index')->with('session');
    }
    
}
<?php

namespace App\Http\Controllers;
use App\Models\Stocks;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Schools;
use App\Models\Sizes;
use App\Models\order_random_items;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as FacadesDB;
use function Laravel\Prompts\confirm;

class OrderController extends Controller
{
    public function showorder(){
        $sizes = Sizes::all();
        $products = Product::all();
        $schools = Schools::all();
        $stocks = Stocks::all();
        return view('forms.order', [
            'sizes' => $sizes,
            'products' => $products,
            'schools' => $schools,
            'stocks' => $stocks
        ]);
    }
    public function phoneCheck(Request $request){
        $request->phone;
       $user = User::where('phone', 'like', '%' . $request->phone . '%')->get();
       return response()->json($user);
    }
    public function nameCheck(Request $request){
        $request->customer_name;
       $user = User::where('name', 'like', '%' . $request->customer_name . '%')->get();
       return response()->json($user);
    }

    public function createorder(Request $request)
    {
        try {
            $request->validate([
                'receivedamount' => 'required',
                'items' => 'required|array',
                'items.*.school' => 'required|integer|exists:schools,id',
                'items.*.product' => 'required|integer|exists:products,id',
                'items.*.size' => 'required|integer|exists:sizes,id',
                'items.*.price' => 'required|numeric|min:1',
                'items.*.quantity' => 'required|numeric|min:1',
                'random.*.product' => 'required|alpha|max:255',
                'random.*.price' => 'required|numeric|min:1',
                'random.*.quantity' => 'required|numeric|min:1',
            ]);    
            $user = null;
            $user_id = null;

            FacadesDB::beginTransaction();
            if($request->name && $request->phone){
                $user = User::where('name',$request->name)->where('phone',$request->phone)->first();
                if($user == null){
                    $userObj = new User();
                    $userObj->name = $request->input('name'); //customer name
                    $userObj->phone = $request->input('phone');
                    $userObj->save();
                    $user_id = $userObj->id;
                }
                else{
                    $user_id = $user->id;
                }
            }
            $discoutPercentage =  $request->input('discountpercentage');
            $user = User::where('name',$request->input('name'))
                        ->where('phone',$request->input('phone'))
                        ->first();
                if($user == null){
                    $users  = new User;
                    $users->name = $request->name;
                    $users->phone = $request->phone;
                    $users->save();
                    $user_id = $users->id;
                }else{
                    $user_id = $user->id;
                }
            // Initialize variables for total price and school margin calculations
            $cardPayment = $request->input('cardPayment');
            $order = new Order();
            if($request->input('school1')){
                $order->school_id = $request->input('school1'); //school name
            }
            $order->discount = 0;
            $order->discount_percentage = $discoutPercentage ?? 0;
            $order->school_margin = 0;
            $order->price_after_discount = 0;
            $order->margin_after_discount = 0;
            $order->subtotal = 0;
            if($user_id != null){
                $order->user_id = $user_id;
            }
            $order->total = 0;
            if($cardPayment && $cardPayment == 'on'){
                $order->card_payment = 1;
            }
            $order->received_amount = $request->input('receivedamount');
            $order->remaining_amount = $request->input('remainingamount') ?? 0;
            $order->gst = $request->input('gst', 0.00);
            $order->user_id = $user_id;
            $order->save();

            $discoutPercentage =  $request->input('discountpercentage');
            $sumSchoolMargin = 0;
            $sumSchoolMarginAfterDiscount = 0;
            $sumProductPrice = 0;
            $sumProductPriceAfterDiscount = 0;

            foreach ($request->input('items') as $item) {

                $stock = Stocks::where('school_id', $item['school'])
                    ->where('product_id', $item['product'])
                    ->where('size_id', $item['size'])
                    ->where('quantity', '>', 0)
                    ->first();

                if ($stock && $stock->quantity < 1) {
                    return response()->json(['error' => 'Quantity for this item is low.'], 422);
                }

                $quantity = (int) $item['quantity'];

                $schooPerecntage = Schools::find($item['school'])->percentage;

                $totalProdcutPrice = $stock->sale_price * ($quantity > 0 ? $quantity : 1);
                $productDiscount = $totalProdcutPrice > 0 ? ($totalProdcutPrice * $discoutPercentage / 100) : 0;
                $productPriceAfterDiscount = $totalProdcutPrice - $productDiscount;
                $sumProductPrice += $totalProdcutPrice;
                $sumProductPriceAfterDiscount += $productPriceAfterDiscount;
                
                $schoolPriceMargin = $totalProdcutPrice > 0 ? ($totalProdcutPrice * $schooPerecntage / 100) : 0; // 120
                $marginAfterDiscount = $schoolPriceMargin - ($schoolPriceMargin * $discoutPercentage / 100);
                $sumSchoolMargin += $schoolPriceMargin;
                $sumSchoolMarginAfterDiscount += $marginAfterDiscount;

                $total = $totalProdcutPrice + $schoolPriceMargin;
                $totalDiscoutn = $total * $discoutPercentage / 100;
                
                $orderItem = new OrderItem();
                $orderItem->school_id = $item['school'];
                $orderItem->product_id = $item['product'];
                $orderItem->size_id = $item['size'];
                $orderItem->price = $stock->sale_price;
                $orderItem->quantity = $quantity;
                $orderItem->total = $total;
                $orderItem->discount = $totalDiscoutn;
                $orderItem->school_margin = $schoolPriceMargin;
                $orderItem->price_after_discount = $productPriceAfterDiscount;
                $orderItem->margin_after_discount = $marginAfterDiscount;
                $orderItem->returned = 0;
                $orderItem->order_id = $order->id;    
                $orderItem->save();
            
                // Update stock quantity
                $stock->quantity -= $quantity;
                $stock->save();
            }

            $totalOrder = $sumProductPrice + $sumSchoolMargin;
            $discount  = $totalOrder * $discoutPercentage / 100;

            $order->discount = $discount;
            $order->discount_percentage = $discoutPercentage ?? 0;
            $order->school_margin = $sumSchoolMargin;
            $order->price_after_discount = $sumProductPriceAfterDiscount;
            $order->margin_after_discount = $sumSchoolMarginAfterDiscount;
            $order->subtotal = $totalOrder;
            $order->total = $totalOrder - $discount;
            $order->update();

            // Send email notification
            $emailController = new Emailcontroller();
            $emailController->lowstock();

            FacadesDB::commit();
            return response()->json(['success' => true, 'order_id' => $order->id]);
        } catch (\Throwable $th) {
            FacadesDB::rollBack();
            throw $th;
        }
    }

    public function orderindex(Request $request) {
        $search = $request->input('search', '');
        $orders = Order::with(['orderItems', 'school']); // Load school relationship
        
        if (!empty($search)) {
            $orders = Order::leftJoin('users as u', 'u.id', '=', 'orders.user_id')
                            ->select('u.id as u_id', 'orders.id as order_id', 'u.*', 'orders.*')
                            ->whereHas('orderItems', function($query) use ($search) {
                      $query->where('u.phone', $search)->orWhere('u.name', 'like', '%' . $search . '%')->orWhere('orders.id', 'like',  $search );
            })
            ->with('school')
            ->orderBy('orders.id', 'DESC')
            ->paginate(10);
        } else {
            $orders = Order::select('orders.*', 'u.*')
                            ->leftJoin('users as u', 'u.id', '=', 'orders.user_id')
                            ->select('u.id as u_id', 'orders.id as order_id', 'u.*', 'orders.*')
                            ->with('school')
                            ->orderBy('orders.id', 'DESC')
                            ->paginate(10);
             }   
        return view('tables.orderindex', ['orders' => $orders, 'search' => $search]);
    }
    
    // public function editorder($id){
    //     $sizes = Sizes::all();
    //     $products = Product::all();
    //     $schools = Schools::all();
    //     $orders = Order::with(['orderItems', 'orderRandomItems'])->find($id);
    //     return view('forms.updateorder', [
    //         'sizes' => $sizes,
    //         'products' => $products,
    //         'schools' => $schools,
    //         'orders' => $orders,
    //     ]);       
    // }
    // public function updateorder(Request $request, $id) {
    //     // Validate request data
    //     $request->validate([
    //         'name' => 'required|max:255',
    //         'items' => 'required|array',
    //         'items.*.school' => 'required|integer|exists:schools,id',
    //         'items.*.product' => 'required|integer|exists:products,id',
    //         'items.*.size' => 'required|integer|exists:sizes,id',
    //         'items.*.price' => 'required|numeric|min:1',
    //         'items.*.quantity' => 'required|numeric|min:1',
    //         'random.*.product' => 'required|alpha|max:255',
    //         'random.*.price' => 'required|numeric|min:1',
    //         'random.*.quantity' => 'required|numeric|min:1',
    //     ]);
    //     $order = Order::find($id);
    //     if (!$order) {
    //         return redirect()->route('order.show')->withErrors('Order not found.');
    //     }
    //     // Update main order details
    //     $order->customer_name = $request->input('name');
    //     $order->subtotal = $request->input('showsubtotal');
    //     $order->discount_percentage = $request->input('discount');
    //     $order->total = $request->input('total_price');
    //     $order->discount = $request->input('discount_total');
    //     $order->school_margin = $request->input('school_margin', 0.00);
    //     $order->gst = $request->input('gst', 0.00);
    //     $order->save();    
    //     // Handle order random items
    //     $randomItems = $request->input('random', []);
    //     $existingRandomItems = $order->orderRandomItems()->pluck('id')->toArray();
    //     foreach ($randomItems as $random) {
    //         if (isset($random['id'])) {
    //             $randomOrder = order_random_items::find($random['id']);
    //             if ($randomOrder) {
    //                 $randomOrder->product = $random['product'];
    //                 $randomOrder->price = $random['price'];
    //                 $randomOrder->quantity = $random['quantity'];
    //                 $randomOrder->save();
    //                 $existingRandomItems = array_diff($existingRandomItems, [$random['id']]);
    //             }
    //         } else {
    //             $randomOrder = new order_random_items();
    //             $randomOrder->order_id = $order->id;
    //             $randomOrder->product = $random['product'];
    //             $randomOrder->price = $random['price'];
    //             $randomOrder->quantity = $random['quantity'];
    //             $randomOrder->save();
    //         }
    //     }
    //     // Remove deleted random items
    //     order_random_items::destroy($existingRandomItems);
    
    //     // Handle order items
    //     $items = $request->input('items', []);
    //     $existingOrderItems = $order->orderItems()->pluck('id')->toArray();
    //     foreach ($items as $item) {
    //         $stock = Stocks::where('school_id', $item['school'])
    //                        ->where('product_id', $item['product'])
    //                        ->where('size_id', $item['size'])
    //                        ->first();
    
    //         if (isset($item['id'])) {
    //             $orderItem = OrderItem::find($item['id']);
    //             if ($orderItem) {
    //                 // Calculate the difference in quantity and adjust stock
    //                 $quantityDifference = $quantity - $orderItem->quantity;
    //                 $stock->quantity -= $quantityDifference;
    
    //                 $orderItem->school_id = $item['school'];
    //                 $orderItem->product_id = $item['product'];
    //                 $orderItem->size_id = $item['size'];
    //                 $orderItem->price = $item['price'];
    //                 $orderItem->quantity = $quantity;
    //                 $orderItem->discount = $request->input('discount', 0) * $item['price'] * $quantity / 100;
    //                 $orderItem->school_margin = $item['school_margin'];
    //                 $orderItem->save();
    
    //                 $existingOrderItems = array_diff($existingOrderItems, [$item['id']]);
    //             }
    //         } else {
    //             $orderItem = new OrderItem();
    //             $orderItem->school_id = $item['school'];
    //             $orderItem->order_id = $order->id;
    //             $orderItem->product_id = $item['product'];
    //             $orderItem->size_id = $item['size'];
    //             $orderItem->price = $item['price'];
    //             $orderItem->quantity = $quantity;
    //             $orderItem->discount = $request->input('discount', 0) * $item['price'] * $quantity / 100;
    //             $orderItem->school_margin = $item['school_margin'];
    //             $orderItem->save();
    //             // Decrease stock for new order items
    //             $stock->quantity -= $quantity;
    //         }
    
    //         // Save the updated product stock
    //         $stock->save();
    //     }
    
    //     // Remove deleted order items and restore stock
    //     foreach ($existingOrderItems as $itemId) {
    //         $orderItem = OrderItem::find($itemId);
    //         if ($orderItem) {
    //             $stock = Stocks::where('school_id', $orderItem->school_id)
    //                            ->where('product_id', $orderItem->product_id)
    //                            ->where('size_id', $orderItem->size_id)
    //                            ->first();
    //             if ($stock) {
    //                 $stock->quantity += $orderItem->quantity; // Restore stock
    //                 $stock->save();
    //             }
    //             $orderItem->delete();
    //         }
    //     }
    //     Session::flash('success', 'Order Updated Successfully');
    //     return response()->json(['success' => true, 'order_id' => $order->id]);
    // }
    public function deleteorder($id){
        $order = Order::find($id);
        if (!$order) {
            return redirect()->route('orders.index')->with('error', 'Order not found.');
        }
        // Show confirmation alert
        $confirm = confirm("Are you sure you want to delete order ID: $id?");
        if ($confirm) {
            // Delete related items first
            $order->orderItems()->delete();
            $order->orderRandomItems()->delete();
            // Then delete the order itself
            $order->delete();
            return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
        } else {
            return redirect()->route('orders.index')->with('error', 'Deletion cancelled. Order was not deleted.');
        }
    }
    public function viewOrder($id){
        $sizes = Sizes::all();
        $products = Product::all();
        $schools = Schools::all();
        $orderItems = OrderItem::where('order_id', $id)->get();
        // $order = Order::find($id);
        $orders = Order::where('orders.id', $id) 
                        ->leftJoin('users as u', 'u.id', '=', 'orders.user_id')
                        ->leftJoin('schools as sch', 'sch.id', '=', 'orders.school_id')
                        ->select('u.id as u_id', 'orders.id as order_id', 'u.*', 'orders.*','sch.name as school')->first();
        // dd($orders);
        return view('forms.viewOrder', [
            'sizes' => $sizes,
            'products' => $products,
            'schools' => $schools,
            'order' => $orders,
            'orderItems' => $orderItems
        ]);
    }
    public function cancelOrder($id){
        $order = Order::find($id);
        $orderItems = OrderItem::where('order_id', $id)->get()->toArray();
        $items = $orderItems;
        foreach ($items as $key => $item) {
               $school_id  =  $item['school_id'];
               $product_id =  $item['product_id'];
               $size_id    =  $item['size_id'];
               $order_id   =  $item['order_id'];
               $quantity   =  $item['quantity'];

               Stocks::where('school_id', $school_id)
                        ->where('product_id', $product_id)
                        ->where('size_id', $size_id)
                        ->increment('quantity', $quantity);
            }
        $order->cancel_order = 1;
        $order->save();
        return redirect()->route('orders.index')->with('success', 'Order cancelled successfully.');
       
    }
    public function showInvoice($orderId){
        $order = Order::with('orderItems' , 'orderRandomItems' , 'school')->findOrFail($orderId);

        $updated_at = Carbon::createFromTimestamp(strtotime($order->updated_at))
            ->timezone('Asia/Karachi')
            ->toDateTimeString();
        return view('tables.orderInvoice', compact('order', 'updated_at'));
    }
    
} 


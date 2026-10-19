<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Schools;
use App\Models\Sizes;
use App\Models\Stocks;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use PhpParser\Node\Scalar\MagicConst\Dir;

// use Illuminate\Http\Request;

class dailySaleReportController extends Controller
{
    public function show(){
        $sizes = Sizes::all();
        $products = Product::all();
        $schools = Schools::all();
        return view('forms.dailySaleReport', [
            'sizes' => $sizes,
            'products' => $products,
            'schools' => $schools,
        ]);
    }
    public function fetchDailySales(Request $request){
        $request->validate([
            'fromdate' => 'required|date_format:Y-m-d',
            'todate' => 'required|date_format:Y-m-d|after_or_equal:fromdate',
        ]);
        $query = Order::query();
        // Apply filters based on request parameters
        if ($request->filled('fromdate') && $request->filled('todate')) {
            $fromDate = $request->fromdate;
            $toDate = date('Y-m-d', strtotime($request->todate)); 
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }
        $relationConditions = [];
        if ($request->filled('school') && $request->school != '0') {
            $relationConditions[] = ['school_id', "=", $request->school];
        }
        if ($request->filled('product') && $request->product != '0') {
            $relationConditions[] = ['product_id', "=", $request->product];
        }
        if ($request->filled('size') && $request->size != '0') {
            $relationConditions[] = ['size_id', "=", $request->size];
        }
        if(count($relationConditions) > 0){
            $query->withAndWhereHas('orderItems', function ($subQuery) use ($relationConditions) {
                $subQuery->where($relationConditions);
                $subQuery->with(['size', 'school', 'product']);
            });
        }
        // Retrieve orders and eager load related models
        $sales = $query->where('cancel_order','=',null)->get();
        $dailySales = [];
        $totalSale = 0;    
        $totalPrice = 0;
        foreach ($sales as $order) {
            foreach ($order->orderItems as $item) {
                $date = $order->created_at->format('Y-m-d');
                $formattedSales[] = [
                    'school'  => $item->school->name,
                    'product' => $item->product->name,
                    'size'    => $item->size->size,
                    'total'   => $order->total,
                    'year'    => $order->created_at->format('Y'),
                    'month'   => $order->created_at->format('m'),
                ];
                if (!isset($dailySales[$date])) {
                    $dailySales[$date] = 0;
                }
                $school_margin = $item->school_margin/$item->quantity;
                    if($item->quantity - $item->returned != 0){
                        $saleQuantity = $item->quantity - $item->returned;
                        $totalPrice = $saleQuantity * ($item->price + $school_margin);
                        $dailySales[$date] += $totalPrice ;
                    }
                // Calculate total sales
                $totalSale += $totalPrice;
            }
        }
        $formattedSales = [
            'success' => true,
            'dailySales' => $dailySales,
            'totalSale' => $totalSale,
        ];
        return response()->json($formattedSales);
    }    
    public function dailySalesReportPdf(Request $request){
        ini_set('max_execution_time', 120); 
        $request->input('fromdate');
        $request->input('todate');
        $request->input('school');
        $request->input('product');
        $request->input('size');
        $query = Order::query();
        // Apply filters based on request parameters
        if ($request->filled('fromdate') && $request->filled('todate')) {
            $fromDate = $request->fromdate;
            $toDate = date('Y-m-d', strtotime($request->todate)); 
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }
        $relationConditions = [];
        if ($request->filled('school') && $request->school != '0') {
            $relationConditions[] = ['school_id', "=", $request->school];
        }
        if ($request->filled('product') && $request->product != '0') {
            $relationConditions[] = ['product_id', "=", $request->product];
        }
        if ($request->filled('size') && $request->size != '0') {
            $relationConditions[] = ['size_id', "=", $request->size];
        }
        if(count($relationConditions) > 0){
            $query->withAndWhereHas('orderItems', function ($subQuery) use ($relationConditions) {
                $subQuery->where($relationConditions);
                $subQuery->with(['size', 'school', 'product']);
            });
        }
        // Retrieve orders and eager load related models
        $sales = $query->where('cancel_order','=',null)->get();
        $dailySales = [];
        $totalSale = 0;    
        $totalPrice = 0;
        foreach ($sales as $order) {
            foreach ($order->orderItems as $item) {
                $date = $order->created_at->format('Y-m-d');
                $formattedSales[] = [
                    'school'  => $item->school->name,
                    'product' => $item->product->name,
                    'size'    => $item->size->size,
                    'total'   => $order->total,
                    'year'    => $order->created_at->format('Y'),
                    'month'   => $order->created_at->format('m'),
                ];
                if (!isset($dailySales[$date])) {
                    $dailySales[$date] = 0;
                }
                $school_margin = $item->school_margin/$item->quantity;
                    if($item->quantity - $item->returned != 0){
                        $saleQuantity = $item->quantity - $item->returned;
                        $totalPrice = $saleQuantity * ($item->price + $school_margin);
                        $dailySales[$date] += $totalPrice ;
                    }
                // Calculate total sales
                $totalSale += $totalPrice;
            }
        }
        $formattedSales = [
            'success' => true,
            'dailySales' => $dailySales,
            'totalSale' => $totalSale,
        ];
        $pdfOptions = new Options();
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);
        $pdfOptions->set('defaultFont', 'Arial');   
        
        $dompdf = new Dompdf($pdfOptions);
        
        $imagePath = public_path('asset/img/logo2.png');
        $html = view('tables.dailySales', ['data' => $formattedSales , 'totalSale' => $totalSale, 'imagePath' => $imagePath])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->stream('daily_sales_report.pdf');
    }
    //
}

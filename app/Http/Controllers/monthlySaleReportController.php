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

class monthlySaleReportController extends Controller
{
    public function show(){
        $sizes = Sizes::all();
        $products = Product::all();
        $schools = Schools::all();
        return view('forms.monthlySaleReport', [
            'sizes' => $sizes,
            'products' => $products,
            'schools' => $schools,
        ]);
    }
    public function fetchSales(Request $request){
        $request->validate([
            'frommonth' => 'required|date_format:Y-m',
            'tomonth' => 'required|date_format:Y-m|after_or_equal:frommonth',
        ]);
        $query = Order::query();
        // Apply filters based on request parameters
        if ($request->filled('frommonth') && $request->filled('tomonth')) {
            $fromDate = $request->frommonth . '-01';
            $toDate = date('Y-m-t', strtotime($request->tomonth)); // End of selected month
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
        $monthlySales = [];
        $totalSale = 0;    
        foreach ($sales as $order) {
            foreach ($order->orderItems as $item) {
                $yearMonth = $order->created_at->format('Y-m');
                $formattedSales[] = [
                    'school' => $item->school->name,
                    'product' => $item->product->name,
                    'size' => $item->size->size,
                    'total' => $order->total,
                    'year' => $order->created_at->format('Y'),
                    'month' => $order->created_at->format('m'),
                ];
                if (!isset($monthlySales[$yearMonth])) {
                    $monthlySales[$yearMonth] = 0;
                }
                $monthlySales[$yearMonth] += $order->total;  
                // Calculate total sales
                $totalSale += $order->total;
            }
        }
        $formattedSales = [
            'success' => true,
            'monthlySales' => $monthlySales,
            'totalSale' => $totalSale,
        ];
        return response()->json($formattedSales);
    }    
    public function salesReportPdf(Request $request){
        ini_set('max_execution_time', 120); 
        $request->input('frommonth');
        $request->input('tomonth');
        $request->input('school');
        $request->input('product');
        $request->input('size');
        $query = Order::query();
        // Apply filters based on request parameters
        if ($request->filled('frommonth') && $request->filled('tomonth')) {
            $fromDate = $request->frommonth . '-01';
            $toDate = date('Y-m-t', strtotime($request->tomonth)); // End of selected month
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }
        if ($request->filled('school') && $request->school != '0') {
            $query->whereHas('orderItems', function ($subQuery) use ($request) {
                $subQuery->where('school_id', $request->school);
            });
        }
        if ($request->filled('product') && $request->product != '0') {
            $query->whereHas('orderItems', function ($subQuery) use ($request) {
                $subQuery->where('product_id', $request->product);
            });
        }
        if ($request->filled('size') && $request->size != '0') {
            $query->whereHas('orderItems', function ($subQuery) use ($request) {
                $subQuery->where('size_id', $request->size);
            });
        }
        // Retrieve orders and eager load related models
        $sales = $query->with(['orderItems', 'orderItems.school', 'orderItems.product', 'orderItems.size'])->get();
        $monthlySales = [];
        $totalSale = 0;    
        foreach ($sales as $order) {
            foreach ($order->orderItems as $item) {
                $yearMonth = $order->created_at->format('Y-m');
                $formattedSales[] = [
                    'school' => $item->school->name,
                    'product' => $item->product->name,
                    'size' => $item->size->size,
                    'total' => $order->total,
                    'year' => $order->created_at->format('Y'),
                    'month' => $order->created_at->format('m'),
                ];
                if (!isset($monthlySales[$yearMonth])) {
                    $monthlySales[$yearMonth] = 0;
                }
                $monthlySales[$yearMonth] += $order->total;  
                // Calculate total sales
                $totalSale += $order->total;
            }
        }
        $formattedSales = [
            'success' => true,
            'monthlySales' => $monthlySales,
            'totalSale' => $totalSale,
        ];
        $pdfOptions = new Options();
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);
        $pdfOptions->set('defaultFont', 'Arial');   
        
        $dompdf = new Dompdf($pdfOptions);
        
        $imagePath = public_path('asset/img/logo2.png');
        $html = view('tables.sales', ['data' => $formattedSales , 'totalSale' => $totalSale, 'imagePath' => $imagePath])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->stream('sales_report.pdf');
    }
    //
}

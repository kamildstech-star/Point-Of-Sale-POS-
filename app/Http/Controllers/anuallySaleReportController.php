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

class anuallySaleReportController extends Controller
{
    public function show(){
        $sizes = Sizes::all();
        $products = Product::all();
        $schools = Schools::all();
        return view('forms.anuallySaleReport', [
            'sizes' => $sizes,
            'products' => $products,
            'schools' => $schools,
        ]);
    }
    public function fetchAnnuallySales(Request $request){
        $request->validate([
            'fromyear' => 'required',
            'toyear' => 'required|after_or_equal:fromyear',
        ]);
        $query = Order::query();
        // Apply filters based on request parameters
        if ($request->filled('fromyear') && $request->filled('toyear')) {
            $fromDate = $request->fromyear . '-01-01';
            $toDate = $request->toyear.'-12-31'; // End of selected month
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
        $annuallySales = [];
        $totalSale = 0;
        foreach ($sales as $order) {
            foreach ($order->orderItems as $item) {
                $totalPrice = 0;
                $year = $order->created_at->format('Y');
                $formattedSales[] = [
                    'school' => $item->school->name,
                    'product' => $item->product->name,
                    'size' => $item->size->size,
                    'total' => $order->total,
                    'year' => $order->created_at->format('Y'),
                    'month' => $order->created_at->format('m'),
                ];
                if (!isset($annuallySales[$year])) {
                    $annuallySales[$year] = 0;
                }
                $school_margin = $item->school_margin/$item->quantity;
                    if($item->quantity - $item->returned != 0){
                        $saleQuantity = $item->quantity - $item->returned;
                        $totalPrice = $saleQuantity * ($item->price + $school_margin);
                        $annuallySales[$year] += $totalPrice ;
                    }
                    $totalSale += $totalPrice;
            }
        }
        $formattedSales = [
            'success' => true,
            'annuallySales' => $annuallySales,
            'totalSale' => $totalSale,
        ];
        return response()->json($formattedSales);
    }    
    public function anuallysalesReportPdf(Request $request){
        ini_set('max_execution_time', 120); 
        $request->input('fromyear');
        $request->input('toyear');
        $request->input('school');
        $request->input('product');
        $request->input('size');
        $query = Order::query();
        // Apply filters based on request parameters
        if ($request->filled('fromyear') && $request->filled('toyear')) {
            $fromDate = $request->fromyear . '-01-01';
            $toDate = $request->toyear.'-12-31'; // End of selected month
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
        $annuallySales = [];
        $totalSale = 0;
        foreach ($sales as $order) {
            foreach ($order->orderItems as $item) {
                $totalPrice = 0;
                $year = $order->created_at->format('Y');
                $formattedSales[] = [
                    'school' => $item->school->name,
                    'product' => $item->product->name,
                    'size' => $item->size->size,
                    'total' => $order->total,
                    'year' => $order->created_at->format('Y'),
                    'month' => $order->created_at->format('m'),
                ];
                if (!isset($annuallySales[$year])) {
                    $annuallySales[$year] = 0;
                }
                $school_margin = $item->school_margin/$item->quantity;
                    if($item->quantity - $item->returned != 0){
                        $saleQuantity = $item->quantity - $item->returned;
                        $totalPrice = $saleQuantity * ($item->price + $school_margin);
                        $annuallySales[$year] += $totalPrice ;
                    }
                    $totalSale += $totalPrice;
            }
        }
        $formattedSales = [
            'success' => true,
            'annuallySales' => $annuallySales,
            'totalSale' => $totalSale,
        ];
        // dd($annuallySales);
        $pdfOptions = new Options();
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);
        $pdfOptions->set('defaultFont', 'Arial');   
        
        $dompdf = new Dompdf($pdfOptions);
        
        $imagePath = public_path('asset/img/logo2.png');
        $html = view('tables.anuallySales', ['data' => $formattedSales , 'totalSale' => $totalSale, 'imagePath' => $imagePath])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->stream('anually_sales_report.pdf');
    }
    
}

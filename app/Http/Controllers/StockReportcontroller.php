<?php

namespace App\Http\Controllers;

use App\Models\settings;
use App\Models\Stocks;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use App\Http\Constants\AppConst;

class StockReportcontroller extends Controller
{
    public function show(){
        return view('forms.stockReport');
    }
    public function stockReport(Request $request) {
        $lowStockkey = Settings::where('key', AppConst::LOW_STOCK_ALERT)->first();
        $value = $lowStockkey ? (int)$lowStockkey->value : 10;

        if ($request->has('low_stock')) {
            $stocks = Stocks::with(['size', 'product', 'school'])
                            ->where('quantity', '<=', $value)->get();
        } else {
            $stocks = Stocks::with(['size', 'product', 'school'])->get();
        }

        $formattedStocks = $stocks->map(function ($stock) {
            return [
                'quantity' => $stock->quantity,
                'school' => $stock->school->name,
                'product' => $stock->product->name,
                'size' => $stock->size->size,
            ];
        });
        return response()->json([
            'success' => true,
            'data' => $formattedStocks,
        ]);
    }
    public function stockReportpdf(Request $request) {
        ini_set('max_execution_time', 120);
        $lowStockkey = Settings::where('key', AppConst::LOW_STOCK_ALERT)->first();
        $value = $lowStockkey ? (int)$lowStockkey->value : 10;
        $fullStock = $request->input('fullstock');
        $lowStock = $request->input('lowstock');
        if ($lowStock) {
            $stocks = Stocks::with(['size', 'product', 'school'])
                            ->where('quantity', '<=', $value)->get();
        } else {
            $stocks = Stocks::with(['size', 'product', 'school'])->get();
        }

        $formattedStocks = $stocks->map(function ($stock) {
            return [
                'quantity' => $stock->quantity,
                'school' => $stock->school->name,
                'product' => $stock->product->name,
                'size' => $stock->size->size,
            ];
        })->toArray();
        $pdfOptions = new Options();
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isRemoteEnabled', true);
        $pdfOptions->set('defaultFont', 'Arial');   

        $dompdf = new Dompdf($pdfOptions);
        
        $imagePath = public_path('asset/img/logo2.png');
        $html = view('tables.stockReport', ['data' => $formattedStocks , 'imagePath' => $imagePath])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->stream('stock_report.pdf');
    }
}

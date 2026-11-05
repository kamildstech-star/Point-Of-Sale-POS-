<?php

namespace App\Http\Controllers;

use App\Models\BussinessDealer;
use App\Models\Ledgers;
use Illuminate\Http\Request;

class ledgerController extends Controller
{
    public function ledgerindex($id = null){
        if($id != null){
            $bussinessDealer = BussinessDealer::with("ledger")->find($id);
            if($bussinessDealer){
                return view('tables.ledger',['bussinessDealer' => $bussinessDealer, 'id' => $id]);
            }else{
                abort('404');
            }
        }else{
            abort('404');
        }
    }

    public function create(Request $request){
        $balance = 0;
        $dealer = BussinessDealer::find($request->id);
        $ledger = Ledgers::where('ledgerable_type','App\Models\BussinessDealer')
                        ->where('ledgerable_id',$request->id)
                        ->latest('created_at')
                        ->first();
        $type = $request->type;
        $credit = null;
        $debit = null;
        if($type == 2){
            $credit = $request->amount;
            if($ledger){
                $balance = $ledger->balance - $credit;
            }else{
                $balance =  $credit;
            }
        }else if($type == 1)
        {
            $debit = $request->amount ;
            if($ledger){
                $balance = $ledger->balance + $debit;
            }else{
                $balance =  $debit;
            }
        }
        $dealer->ledger()->create([
            "description"=>$request->description,
            "debit"=>$debit,
            "credit"=>$credit,
            "balance_type"=>$type,
            "balance"=>$balance,
            ]
        );
        return redirect()->back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\BussinessDealer;
use App\Models\DailyAccounts;
use App\Models\Ledgers;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Session;

class DailyAccountController extends Controller
{

    public function show(Request $request){
        $Parties = BussinessDealer::where('type', '1')->get();
        $Provider = BussinessDealer::where('type', '2')->get();
        $closing_balance = 0;
        $isEditable = true; 
        $daily_account = null;
        $daily_accounts = null;
        $da_id = null;
        if($request->date != null){
            $sales = Order::whereDate('created_at', $request->date)->where('cancel_order','=' ,null)->get();
            $ledger = Ledgers::whereDate('ledgers.created_at', $request->date)
                            ->leftJoin('business_dealers as bd','bd.id','=','ledgers.ledgerable_id')
                            ->select('ledgers.id as l_id','bd.id as bd_id','ledgers.*', 'bd.*')
                            ->get();
            $dailyAccount = DailyAccounts::whereDate('created_at',$request->date)->get();
            if(count($dailyAccount)){
                $da_id = $dailyAccount[0]->id;
            }
            $daily_account = DailyAccounts::latest()->first();
            $date = $daily_account->created_at->format('Y-m-d');
            $currentDate = now()->format('Y-m-d');
            if($request->date != $currentDate){
                if($currentDate == $date){
                    $isEditable = false; 
               }
               else
               {
                   $isEditable = true; 
               }
            }
        }else{
            $sales = Order::whereDate('created_at', Carbon::today())->where('cancel_order','=' ,null)->get();
            $ledger = Ledgers::whereDate('ledgers.created_at', Carbon::today())
                            ->leftJoin('business_dealers as bd','bd.id','=','ledgers.ledgerable_id')
                            ->select('ledgers.id as l_id','bd.id as bd_id','ledgers.*', 'bd.*')
                            ->get();
            $daily_accounts = DailyAccounts::whereDate('created_at', '<',Carbon::today())->latest()->first();
        }
        $total_sale = 0;
        if($daily_accounts){
            $closing_balance = $daily_accounts->closing_balance;
        }
        if(count($sales) != 0){
            foreach ($sales as $key => $sale) {
                $total_sale += $sale->total;
            }
        }
        else
        {
            $total_sale = 0;
        }

        return view("forms.dailyAccount",['providers'=>$Provider, 'parties'=>$Parties , 'total_sale' => $total_sale, 'closing_balance'=> $closing_balance, 'ledgers'=>$ledger, 'isEditable'=>$isEditable, 'date'=>$request->date, 'da_id'=>$da_id]);
    }

    public function create(Request $request){
        $da_id = $request->da_id;
        if($da_id){
            $dailyaccount = DailyAccounts::find($da_id);
            $in = $dailyaccount->in;
            if($in){
                $in = json_decode($in);
                $in = array_merge($in, $request->in);
                $in= array_values(array_unique($in));
                $in = json_encode($in);
              
            }else{
                $in = json_encode($request->in);

            }
            $out = $dailyaccount->out;
            if($out){
                $out = json_decode($out);
                $out = array_merge($out, $request->out);
                $out= array_values(array_unique($out));
                $out = json_encode($out);
            }else{
                $out = json_encode($request->out);
            }
            $dailyaccount->closing_balance = $request->closing_balance;
            $dailyaccount->sale = $request->sale;
            $dailyaccount->total_in = $request->total_in;
            $dailyaccount->total_out = $request->total_out;
            $dailyaccount->in = $in;
            $dailyaccount->out = $out;
            $dailyaccount->save();
        }
        else
        {
            $daily_accounts = DailyAccounts::whereDate('created_at', '=',$request->date)->latest()->first();
            if($daily_accounts)
            {
                if($request->in){
                    $in = json_encode($request->in);
                }
                if($request->out){
                    $out = json_encode($request->out);
                }
                $daily_accounts->closing_balance = $request->closing_balance;
                $daily_accounts->sale = $request->sale;
                $daily_accounts->total_in = $request->total_in;
                $daily_accounts->total_out = $request->total_out;
                $daily_accounts->in = $in;
                $daily_accounts->out = $out;
                $daily_accounts->save();
            }
            else
            {
                $in = null;
                $out = null;
                $daily_account = new DailyAccounts();
                $daily_account->closing_balance = $request->closing_balance;
                $daily_account->sale = $request->sale;
                $daily_account->total_in = $request->total_in;
                $daily_account->total_out = $request->total_out;
                if($request->in){
                    $in = json_encode($request->in);
                }
                if($request->out){
                    $out = json_encode($request->out);
                }
                $daily_account->in = $in;
                $daily_account->out = $out;
                $daily_account->save();
            }
        }
        Session::flash('success', 'daily Account Added Successfully');
        return redirect()->back();
    }

    public function dailyIncome(Request $request){
        $balance = 0;
        $dealer = BussinessDealer::find($request->party);
        $ledger = Ledgers::where('ledgerable_type','App\Models\BussinessDealer')
                        ->where('ledgerable_id',$request->party)
                        ->latest('created_at')
                        ->first();
        $debit = null;
        $description = $request->description;
        $debit = $request->amount ;
        if($ledger != null){
            $balance = $ledger->balance + $debit;
        }
        else{
            $balance = $debit;
        }
        $dealer->ledger()->create([
            "description" => $description,
            "debit"=>$debit,
            "balance"=>$balance,
            "created_at"=>Carbon::parse($request->dates)->timestamp,
            "updated_at"=>Carbon::parse($request->dates)->timestamp,
            ]
        );
        return redirect()->back();
    }
    public function dailyOutcome(Request $request){
        $balance = 0;
        $dealer = BussinessDealer::find($request->provider);
        $ledger = Ledgers::where('ledgerable_type','App\Models\BussinessDealer')
                        ->where('ledgerable_id',$request->provider)
                        ->latest('created_at' , $request->date)
                        ->first();
        $credit = null;
        $description = $request->description;
        $credit = $request->amount ;
        if($ledger != null){
            $balance = $ledger->balance + $credit;
        }
        else{
            $balance = $credit;
        }
        $dealer->ledger()->create([
            "description" => $description,
            "credit"=>$credit,
            "balance"=>$balance,
            "created_at"=>Carbon::parse($request->dates)->timestamp,
            "updated_at"=>Carbon::parse($request->dates)->timestamp,
            ]
        );
        return redirect()->back();
    }
    public function deleteledger($id){
        $ledger = Ledgers::find($id);
        if($ledger) {
            $ledger->delete();
        }
        Session::flash('success', 'Ledger Deleted Successfully');
        return redirect()->route('dailyAccount');
    }
}

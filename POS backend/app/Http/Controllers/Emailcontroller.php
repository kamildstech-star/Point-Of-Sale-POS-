<?php

namespace App\Http\Controllers;

use App\Mail\LowStockEmail;
use App\Mail\WelcomeEmail;
use App\Models\settings;
use App\Models\Stocks;
use App\Models\User;
use App\Http\Constants\AppConst;
use Illuminate\Support\Facades\Mail;

class Emailcontroller extends Controller
{
    public function lowstock() {
        $lowStockkey = settings::where('key', AppConst::LOW_STOCK_ALERT)->first();
        $value = $lowStockkey ? (int)$lowStockkey->value : 10;
        $lowStockItems = Stocks::where('quantity', '<', $value)
                        ->with('school', 'product', 'size')
                        ->get();
        
        if ($lowStockItems->isNotEmpty()) {
            $admins = User::where('role_id', 1)->get();
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new LowStockEmail($lowStockItems));
            }
        } else {
            return view('mail.LowStockEmail', [
                'success' => false,
                'message' => 'No low stock products.'
            ]);
        }
    }
}
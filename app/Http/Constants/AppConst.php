<?php
namespace App\Http\Constants;

class AppConst {
    public const LOW_STOCK_ALERT = "low_stock_alert";

    public const LOGS_TYPE = [
        'create' => 1,
        'update' => 2,
        'delete' => 3
    ];
    public const BUSINESS_DEALER_TYPE = [
        'provider' => 1,
        'party' => 2,
        'owner' => 3
    ];
    public const PERMISSION = [
        "user"=> 7,
        "product"=> 8,
        "size"=> 9,
        "school"=> 10,
        "stock"=> 11,
        "order"=> 12,
        "return"=> 13,
        "report"=> 14,
        "account"=> 15,
        "bussinessDealer"=> 16,
        "setting"=> 17,
        "role"=> 18,
        "dashboard"=> 20,
    ];
}
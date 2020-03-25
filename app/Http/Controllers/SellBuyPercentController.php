<?php

namespace App\Http\Controllers;

use App\logic\SellBuyPercent_logic;
use Illuminate\Http\Request;

class SellBuyPercentController extends Controller
{
    // 計算+轉存

    public function count_data(Request $request)
    {
        SellBuyPercent_logic::getInstance()->count_data_logic((int) $request->code);

        return response('done', 200)->header('Content-Type', 'text/plain');
    }

    // 報表

    public function get_buy_sell_report(Request $request)
    {
        return response()->json(SellBuyPercent_logic::getInstance()->get_buy_sell_report($request));
    }
}

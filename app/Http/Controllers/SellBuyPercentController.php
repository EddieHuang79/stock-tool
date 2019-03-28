<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\logic\SellBuyPercent_logic;

class SellBuyPercentController extends Controller
{

	// 取得資料

	public function get_data(Request $request)
	{

		$code = $request->code;

		$start = $request->s;

		$end = $request->e;

		SellBuyPercent_logic::get_stock_data( $code, $start, $end );

		return response( "done" , 200 )->header('Content-Type', 'text/plain');    	

	}

	// 計算+轉存

    public function count_data(Request $request)
    {

    	$code = $request->code;

		// 		將檔案數據轉存資料庫

		SellBuyPercent_logic::file_to_db( $code );

		// 		計算收盤成交價差

		SellBuyPercent_logic::count_spread( $code );

		// 		計算買盤1

		SellBuyPercent_logic::count_buy1( $code );

		// 		計算賣盤1

		SellBuyPercent_logic::count_sell1( $code );

		// 		計算買盤2

		SellBuyPercent_logic::count_buy2( $code );

		// 		計算賣盤2

		SellBuyPercent_logic::count_sell2( $code );

		// 		計算漲幅總和、跌幅總和、買盤力道張數、賣盤力道張數

		SellBuyPercent_logic::count_pro_data( $code );

		// 		計算20天總買盤、20天總賣盤、買賣壓力道比例

		SellBuyPercent_logic::count_20days_data_and_result( $code );

		return response( "done" , 200 )->header('Content-Type', 'text/plain');    	

    }


	// 股票清單

	public function get_stock_list(Request $request)
	{

		return response()->json( SellBuyPercent_logic::get_stock_list_logic()  );  

	}


	// 報表

	public function get_buy_sell_report(Request $request)
	{

		return response()->json( SellBuyPercent_logic::get_buy_sell_report( $request )  );  

	}

}
